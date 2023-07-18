{{/*
Return the proper documentpreviewservice image name
*/}}
{{- define "documentpreviewservice.image" -}}
{{ include "common.images.image" (dict "imageRoot" .Values.documentpreviewservice.image "global" .Values.global) }}
{{- end -}}

{{/*
Return the proper image name (for the init container volume-permissions image)
*/}}
{{- define "peertube.volumePermissions.image" -}}
{{- include "common.images.image" ( dict "imageRoot" .Values.volumePermissions.image "global" .Values.global ) -}}
{{- end -}}

{{/*
Return the proper image name (for the init container postgres-wait image)
*/}}
{{- define "peertube.postgresWait.image" -}}
{{- include "common.images.image" ( dict "imageRoot" .Values.postgresWait.image "global" .Values.global ) -}}
{{- end -}}

{{/*
Return the proper Docker Image Registry Secret Names
*/}}
{{- define "documentpreviewservice.imagePullSecrets" -}}
{{- include "common.images.pullSecrets" (dict "images" (list .Values.documentpreviewservice.image) "global" .Values.global) -}}
{{- end -}}

{{/*
Return true if cert-manager required annotations for TLS signed certificates are set in the Ingress annotations
Ref: https://cert-manager.io/docs/usage/ingress/#supported-annotations
*/}}
{{- define "peertube.ingress.certManagerRequest" -}}
{{ if or (hasKey . "cert-manager.io/cluster-issuer") (hasKey . "cert-manager.io/issuer") }}
    {{- true -}}
{{- end -}}
{{- end -}}

{{/*
Return the peertube configuration secret
*/}}
{{- define "peertube.secretName" -}}
{{- if .Values.peertube.secret.existing_secret -}}
    {{- printf "%s" (tpl .Values.peertube.secret.existing_secret $) -}}
{{- else -}}
    {{- printf "%s-secret-config" (include "common.names.fullname" .) | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}

{{/*
Return true if a secret object should be created for peertube configuration
*/}}
{{- define "peertube.createSecret" -}}
{{- if not .Values.peertube.secret.existing_secret }}
    {{- true -}}
{{- end -}}
{{- end -}}

{{/*
Return the redis hostname
*/}}
{{- define "peertube.redis.hostname" -}}
{{- if .Values.peertube.redis.useCRD }}
    {{- printf "%s-redis" (include "common.names.fullname" .) | trunc 63 | trimSuffix "-" -}}
{{- else -}}
    {{- .Values.peertube.redis.hostname }}
{{- end -}}
{{- end -}}

{{/*
Return the database hostname
*/}}
{{- define "peertube.database.hostname" -}}
{{- if .Values.peertube.database.useCRD }}
    {{- printf "%s-postgres" (include "common.names.fullname" .) | trunc 63 | trimSuffix "-" -}}
{{- else -}}
    {{- .Values.peertube.database.hostname }}
{{- end -}}
{{- end -}}

{{/*
Return the database configuration secret
*/}}
{{- define "peertube.database.secretName" -}}
{{- if .Values.peertube.database.existing_secret -}}
    {{- printf "%s" (tpl .Values.peertube.database.existing_secret $) -}}
{{- else if .Values.peertube.database.useCRD }}
    {{- "postgres-peertube"}}
{{- else -}}
    {{- printf "%s-database-config" (include "common.names.fullname" .) | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}

{{/*
Return true if a secret object should be created for database configuration
*/}}
{{- define "peertube.database.createSecret" -}}
{{- if and (not .Values.peertube.database.useCRD) (not .Values.peertube.database.existing_secret) }}
    {{- true -}}
{{- end -}}
{{- end -}}

{{/*
Return the smtp configuration secret
*/}}
{{- define "peertube.smtp.secretName" -}}
{{- if .Values.peertube.smtp.existing_secret -}}
    {{- printf "%s" (tpl .Values.peertube.smtp.existing_secret $) -}}
{{- else -}}
    {{- printf "%s-smtp-config" (include "common.names.fullname" .) | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}

{{/*
Return true if a secret object should be created for smtp configuration
*/}}
{{- define "peertube.smtp.createSecret" -}}
{{- if not .Values.peertube.smtp.existing_secret }}
    {{- true -}}
{{- end -}}
{{- end -}}

{{/*
Compile all warnings into a single message.
*/}}
{{- define "peertube.validateValues" -}}
{{- $messages := list -}}
{{- $messages := append $messages (include "peertube.validateValues.foo" .) -}}
{{- $messages := append $messages (include "peertube.validateValues.bar" .) -}}
{{- $messages := without $messages "" -}}
{{- $message := join "\n" $messages -}}

{{- if $message -}}
{{-   printf "\nVALUES VALIDATION:\n%s" $message -}}
{{- end -}}
{{- end -}}
