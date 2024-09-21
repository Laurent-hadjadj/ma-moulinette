
    # Pas de traces et mise en cache pour les fichiers suivants :
    location ~* \.(jpg|jpeg|png||ico|css|js|mp3|ttf|eot|woff|woff2)$ {
      access_log off;
      add_header Cache-Control "public, max-age=31536000, immutable";
    }

    # Pas de traces et mise en cache pour les fichiers suivants :
    location ~* \.(css|js|webmanifest)$ {
    access_log off;
    add_header Cache-Control "public, max-age=86400, immutable";
    }
