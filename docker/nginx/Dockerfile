FROM nginx:alpine

ENV PASSPHRASE Wnv8out25usORrnwAz3dEBL3GxZfFi6pwazjzrfmbZZh8XrIb50AefHGoxn9ddzdt7HF6es0DGYOTovBQVPsMCXfLLtOW07qJy5oqZiwRRgnnMJ

RUN mkdir -p /srv/cert/
WORKDIR /srv/cert/
RUN apk add --update openssl \
    && openssl genrsa -des3 -out server.key -passout env:PASSPHRASE 1024 \
    && openssl rsa -in server.key -out server.key -passin env:PASSPHRASE \
    && openssl req -new -key server.key -out server.csr -subj "/C=FR/ST=/O=Maison/localityName=Maison/commonName=livres/organizationalUnitName=Maison/email=admin@example.com" \
    && openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt

EXPOSE 443