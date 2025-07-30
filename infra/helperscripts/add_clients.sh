export ORY_SDK_URL=http://127.0.0.1:4445

client=$(hydra create client \
    --format json \
    --name "Eurofurence IAM" \
    --scope "offline_access offline openid email admin profile groups groups.read groups.write groups.delete" \
    --redirect-uri http://identity.eurofurence.lan/auth/callback \
    --frontchannel-logout-callback http://identity.eurofurence.lan/auth/frontchannel-logout)
client_id=$(echo $client | jq -r '.client_id')
client_secret=$(echo $client | jq -r '.client_secret')

# Update ../.env file and set OIDC_MAIN_CLIENT_ID and OIDC_MAIN_SECRET
sed -i "s/.*OIDC_MAIN_CLIENT_ID=.*/OIDC_MAIN_CLIENT_ID=$client_id/" .env
sed -i "s/.*OIDC_MAIN_SECRET=.*/OIDC_MAIN_SECRET=$client_secret/" .env

client=$(hydra create client \
    --format json \
    --name "Eurofurence IAM Admin" \
    --scope "offline_access offline openid email admin profile groups groups.read groups.write groups.delete" \
    --redirect-uri http://identity.eurofurence.lan/admin/callback \
    --frontchannel-logout-callback http://identity.eurofurence.lan/admin/frontchannel-logout)

client_id=$(echo $client | jq -r '.client_id')
client_secret=$(echo $client | jq -r '.client_secret')

# Update .env file and set OIDC_MAIN_CLIENT_ID and OIDC_MAIN_SECRET
sed -i "s/.*OIDC_ADMIN_CLIENT_ID=.*/OIDC_ADMIN_CLIENT_ID=$client_id/" .env
sed -i "s/.*OIDC_ADMIN_SECRET=.*/OIDC_ADMIN_SECRET=$client_secret/" .env
