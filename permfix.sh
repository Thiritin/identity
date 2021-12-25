# !/bin/bash
sudo addgroup --gid 1024 developers
sudo usermod -a -G developers $(whoami)
sudo chmod -R 775 ./
sudo chown -R :1024 ./
git config core.filemode false
