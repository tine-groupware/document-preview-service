# Documentation
at least a little bit

## install

requirements: `graphicsmagick libapache2-mod-php7.0 composer php7.0-xml php7.0-cli php7.0-mbstring ghostscript unzip libreoffice`

create a configuration file:  `/etc/documentPreview/config.php`

configure the web server in a way that it rewrites the queries to `public/index.php`

the SSLAuth modul requires SslClientAuth and SSLExportCertData

sample testing apache2 configure

``` ruby
<VirtualHost 127.0.0.1:443>
  DocumentRoot /path/to/documentPreview/public

  RewriteEngine On
  # The following rule allows authentication to work with fast-cgi
  RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
  # The following rule tells Apache that if the requested filename
  # exists, simply serve it.
  RewriteCond %{REQUEST_FILENAME} -s [OR]
  RewriteCond %{REQUEST_FILENAME} -l [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^.*$ - [NC,L]

  # The following rewrites all other queries to index.php. The
  # condition ensures that if you are using Apache aliases to do
  # mass virtual hosting, the base path will be prepended to
  # allow proper resolution of the index.php file; it will work
  # in non-aliased environments as well, providing a safe, one-size
  # fits all solution.
  RewriteCond %{REQUEST_URI}::$1 ^(/.+)(.+)::\2$
  RewriteRule ^(.*) - [E=BASE:%1]
  RewriteRule ^(.*)$ %{ENV:BASE}index.php [NC,L]

  SSLEngine on
  # HTTPS Setup
  SSLCertificateFile      /path/to/server.cert.pem
  SSLCertificateKeyFile /path/to//server.key.pem
  SSLCertificateChainFile /path/to//ca.chain.pem
  # SSL certificate to check client certificates
  SSLCACertific

    public function getDependencies()
    {
        return [
            'factories'  => [
                Action\DocumentPreview::class => Factory\DocumentPreviewFactory::class,
            ],
        ];
    }ateFile /path/to/ca.cert.pem
  # Should be optional to allow other auth methodes
  SSLVerifyClient optional
  # Export Certificate and validation information to php
  SSLOptions +ExportCertData
  SSLOptions +StdEnvVars
<VirtualHost _default_:443>graphicsmagick
```

## config
config for documentPreview, auth config see [auth-middleware](https://gitlab.metaways.net/tine20/auth-middleware)
`/etc/documentPreview/config.php`
``` c
class ConfigProvider
{
    public function __invoke()
    {
        return [
            // configure for documentPreview
            'documentService' => [
                "tempDir" => "temp/", //temp folder
                "downDir" => "download/", //download dir for converted files, should be cleaned regularly
                "downUrl" => "https://download.invalid", //url for download dir
                "maxProc" => 4, //maximum concurrent conversions
                "loggerOut" => "doc.log", // log file documentPreview, can be a file or a zend logger
                //list of allowed extensions
                "ext" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
                //list of libreoffice extensions
                "docExt" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx',
                    ],
                // list of graphicsmagick extensions
                "imgExt" => [
                    'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
            ],

            // routing and authentication setup
            // for authentication documentation see tine20/auth-middleware
            'routes' => [
                // list of routes
                [
                    'name' => 'routeName', // used for identification
                    'path' => '/tine20/documentPreview', // uri prefix for route
                    // sequential list of middelware
                    'middleware' => [
                        Auth\Action\NeedsAuth::class, // auth injector
                        Auth\Action\AuthSSL::class, // ssl auth
                        Auth\Action\AuthCheck::class, // auth check
                        DocumentService\Action\DocumentPreview::class, // DocumentPreview middelware
                    ],
                    'allowed_methods' => ['POST'],
                    // auth settings for this route
                    'auth' =>[
                        'required' => true,
                        'permission' => '(1=1)'
                    ]
                ],
            ],
            // auth settings
            'auth' => [
                'default' => ['name' => 'default', 'permission' => "false",] // default authentication, if no auth is configured auth will fail
            ],
            'authLogger' => 'auth.log', // auth logger, can be a file or a zend logger
        ];
    }
}
```
