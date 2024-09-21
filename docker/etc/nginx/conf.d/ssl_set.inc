# Activation http2
 listen 8443 ssl http2;
 listen [::]:8443 ssl http2;

 # Activation des protections de chiffrement (seulement TLS)
 ssl_protocols TLSv1.2 TLSv1.3;
 ssl_prefer_server_ciphers on;

  ssl_ciphers TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384!aNULL:!eNULL:!EXPORT:!DES:!MD5:!PSK:!RC4;

# Durée de vie de la session SSL
 ssl_session_cache shared:SSL:10m;
 ssl_session_timeout 5m;
 ssl_session_tickets off;

# SALT - Diffie-Hellman paramètre pour DHE ciphersuites
# TODO - régénérer les paramètres DH
# openssl dhparam -out /etc/nginx/ssl/dhparam.pem 2048
 ssl_dhparam /etc/nginx/ssl/dhparam.pem;

# Activation de HSTS (HTTP Strict Transport Security)
 add_header Strict-Transport-Security "max-age=31536000; includeSubdomains;";
