FROM ubuntu:19.10
RUN apt-get update
RUN apt-get install -y php7.3-xml php7.3-cli php7.3-mbstring php7.3-curl
RUN apt-get install -y graphicsmagick ghostscript unzip
RUN apt-get install -y libreoffice
RUN apt-get install -y ssh git

# for gitlab authmiddelware and apt repo
ARG PRIVATE_KEY
ARG KNOWN_HOSTS

RUN mkdir /root/.ssh
RUN echo "${PRIVATE_KEY}" > /root/.ssh/id_ed25519
RUN chmod 600 /root/.ssh/id_ed25519
RUN echo "${KNOWN_HOSTS}" > /root/.ssh/known_hosts
RUN echo "    IdentityFile ~/.ssh/id_ed25519" >> /etc/ssh/ssh_config