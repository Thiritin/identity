# How to run
1. Clone the Repo
2. Run docker-compose up -d --build to start the project
3. Add auth.eurofurence.localhost and identity.eurofurence.localhost to your hosts file and forward them to 127.0.0.1
4. Project has been Setup


Hydra quick commands for local setup
### Admin create client
```
 hydra --endpoint http://127.0.0.1:4445 clients create --secret optimus -n Admin -c https://identity.eurofurence.org/callback,https://identity.eurofurence.org/admin/callback --id ce94f7ac-1c9a-4d5d-8159-56b37562f9b1
```
