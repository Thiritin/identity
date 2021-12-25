# Identity

This is a Laravel, IntertiaJS with Vue based application that works as an OIDC Provider.

## Installation

1. Clone the Repo
2. Run docker-compose up -d --build to start the project
3. Add auth.eurofurence.localhost and identity.eurofurence.localhost to your hosts file and forward them to 127.0.0.1
4. Project has been Setup


### Hydra quick commands for local setup
#### Admin create client
```bash
 docker-compose exec hydra hydra clients create --secret optimus -n Admin -c https://identity.eurofurence.org/callback,https://identity.eurofurence.org/admin/callback --id ce94f7ac-1c9a-4d5d-8159-56b37562f9b1
```

### Security

If you discover any security related issues, please email me@thiritin.com instead of using the issue tracker.

## Credits

-   [Thiritin](https://github.com/thiritin)
-   [All Contributors](../../contributors)
