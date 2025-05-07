# Identity

This is a Laravel, IntertiaJS with Vue based application that works as an OIDC Provider.

## Installation
This project uses [laravel sail](https://laravel.com/docs/11.x/sail) for local development.

1. Clone the Repo
2. Run `sail up -d` to start the docker containers.
3. Run `sail artisan migrate`.
4. Run `sail artisan db:seed`.
5. Run `sail npm install`.
6. Run `sail npm run dev`.
7. Add `auth.eurofurence.localhost` and `identity.eurofurence.localhost` to your hosts file and forward them to `127.0.0.1`.
8. You can now go to http://identity.eurofurence.localhost.

### Security

If you discover any security related issues, please email me@thiritin.com instead of using the issue tracker.
