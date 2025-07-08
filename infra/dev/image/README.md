# Identity Development Image

This image is used to run the Identity development environment with docker-compose/sail.

This image does not contain the full application.
You need to mount sources by mounting root of the project to `/var/www/html` in the container.

## Container Debugging

There are commands to manually build and run the image for debugging purposes.

### Manual Build

Run this command at the root of the repository to build the image:

```bash
docker build -t localhost/ef-identity-dev:latest -f ./infra/dev/image/Containerfile ./infra/dev/image
```

### Manual Run

You can run the entrypoint via:

```bash
docker run --rm -it -v "$(pwd):/var/www/html" -p 80:80 localhost/ef-identity-dev:latest
```

NOTE: `Ctrl+C` here will not stop the server, instead it will restart it. Hit `Ctrl+C` twice to stop the server.

NOTE: If you are using Windows+msys2+docker/podman, you may need to run `export MSYS_NO_PATHCONV=1`
to avoid path conversion issues with `$(pwd)`.

Or run bash shell:

```bash
# runs bash shell as "sail" user
docker run --rm -it -v "$(pwd):/var/www/html" localhost/ef-identity-dev:latest bash

# runs bash shell as "root" user
docker run --rm -it -v "$(pwd):/var/www/html" -e "WWWUSERNAME=root" localhost/ef-identity-dev:latest bash
```
