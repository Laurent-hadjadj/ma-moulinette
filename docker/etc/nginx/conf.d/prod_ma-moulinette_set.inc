## Block http user agent - wget ##
if ($http_user_agent ~* (Wget) ) {
   return 403;
 }

## Block Software download user agents ##
if ($http_user_agent ~* LWP::Simple|BBBike|wget) {
   return 403;
 }

## Only allow these request methods ##
if ($request_method !~ ^(GET|HEAD|POST)$ ) {
   return 444;
 }

## Deny certain Referers ###
if ( $http_referer ~* (babes|forsale|girl|jewelry|love|nudit|organic|poker|porn|sex|teen) ) {
  # return 404;
   return 403;
 }

location / {
   try_files $uri /index.php$is_args$args;
   autoindex_localtime on;
 }

# Accès au fichier index.php
location ~ ^/index\.php(/|$) {
   #limit_req zone=req_zone burst=10 nodelay;
   #Header a ajouter si utilisation de subdirectory http://fqdn.net/chat
   #Si non utilisation d'un subpath alors commenter ou laisser vide
   add_header X-Subpath-Location "/ma-moulinette" always;
   # Check that the PHP script exists before passing it
   try_files $fastcgi_script_name 404;

   include fastcgi_params;
   fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
   fastcgi_param DOCUMENT_ROOT $realpath_root;
   fastcgi_param HTTP_PROXY "";
   fastcgi_param PATH_INFO $fastcgi_path_info;

   fastcgi_buffer_size 128k;
   fastcgi_buffers 4 256k;
   fastcgi_busy_buffers_size 256k;
   fastcgi_send_timeout 600s;
   fastcgi_read_timeout 600s;

   fastcgi_pass php-handler;
   internal;
 }

# On interdit l'execution des fichiers *.php depuis la racine.
location ~ \.php$ {
   return 404;
}

# On interdit l'execution des fichiers *.ht* depuis la racine.
location ~ /\.ht {
   deny  all;
}

# Les logs
# TODO - mettre à jour le noms des logs si besoins.
error_log /var/log/nginx/prod.ma-moulinette_error.log;
access_log /var/log/nginx/prod.ma-moulinette_access.log main;
