# Identity Release Image

This image is used to run the Identity application in production.

This image contains the Identity application and all its dependencies.

## Building the Image

Run this command at the root of the repository to build the image:

```bash
docker build -t localhost/ef-identity-release:latest -f ./infra/release/image/Containerfile --target release .
```
