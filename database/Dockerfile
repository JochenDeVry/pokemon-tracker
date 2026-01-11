FROM mysql:8.0

# Copy init script into the container
COPY init.sql /docker-entrypoint-initdb.d/

# MySQL will automatically execute all .sql files in /docker-entrypoint-initdb.d/
