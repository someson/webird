###################################################################
# You are encouraged to modify the source template and regenerate #
###################################################################

upstream websocket-{{random_hash}} {
  server localhost:{{config.app.wsPort}};
}

upstream webpack-{{random_hash}} {
  server localhost:{{config.dev.webpackPort}};
}

server {
  server_name{% for domain in config.site.domains %} {{ domain }}{% endfor %};
  listen      {{config.app.httpPort}};

  root        "{{config.dev.path.devDir}}public";
  index index.php;

  access_log  "/var/log/nginx/{{config.site.domains[0]}}.log";
  error_log   "/var/log/nginx/{{config.site.domains[0]}}-error.log" error;

  fastcgi_buffer_size 64k;
  fastcgi_buffers 4 64k;

  location = /websocket {
    proxy_pass http://websocket-{{random_hash}};
    proxy_redirect off;
    proxy_set_header    Host              $host;
    proxy_set_header    X-Real-IP $remote_addr;
    proxy_set_header    X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header    X-Forwarded-Proto $scheme;

    # Websocket specific
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";

    # Set the timeout soour connections don't disconnect automatically.
    proxy_connect_timeout 43200000;
    proxy_read_timeout    43200000;
    proxy_send_timeout    43200000;
  }

  # For viewing resources
  location = /webpack-dev-server {
    proxy_pass http://webpack-{{random_hash}}/webpack-dev-server;
    proxy_redirect off;
    proxy_buffering off;
  }

  location ~ ^/(css|js|fonts)/(.+)$ {
    proxy_pass http://webpack-{{random_hash}};
    proxy_redirect off;
    proxy_buffering off;
    rewrite ^/js/webpack-dev-server.js$ /webpack-dev-server.js break;
  }

  location ~ ^/assets/(.+)$ {
    root "{{config.path.appDir}}theme/assets";
    rewrite ^/assets/(.*)$ /$1; break;
  }

  location ~ ^/static/(.+)$ {
    root "{{config.path.appDir}}static";
    rewrite ^/static/(.*)$ /$1; break;
  }

  try_files $uri $uri/ @rewrite;
  location @rewrite {
    rewrite ^/(.*)$ /index.php?url=$1 last;
    break;
  }

  location ~ \.php {
    fastcgi_index  /index.php;
    fastcgi_pass 127.0.0.1:9000;

    include fastcgi_params;
    fastcgi_split_path_info       ^(.+\.php)(/.+)$;
    fastcgi_param PATH_INFO       $fastcgi_path_info;
    fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }
}
