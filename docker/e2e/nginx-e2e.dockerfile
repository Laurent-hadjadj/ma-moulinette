# Image nginx e2e autonome : public/ (assets statiques, bundles EasyAdmin déjà
# générés) est copie a la construction, jamais bind-monte depuis Windows.
# Build context attendu : la racine du depot (../.. depuis docker/e2e/).
FROM nginx:1.27-alpine

COPY docker/e2e/nginx.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/public
