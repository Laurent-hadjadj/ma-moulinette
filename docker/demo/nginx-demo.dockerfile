# Image nginx de la démo. Les fichiers statiques viennent de l'image PHP (assets compilés par asset-map:compile),
# fournie par le contexte nommé « php » du compose. Contexte principal : racine du dépôt.
FROM nginx:1.27-alpine
COPY docker/demo/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=php /var/www/public /var/www/public
