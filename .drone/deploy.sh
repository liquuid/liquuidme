#!/bin/bash
echo 'fazendo rsync dos arquivos'
rsync -avzp src/* liquuid@git.2drasta.com:/srv/liquuidme
echo 'acertando as permissões' 
ssh liquuid@git.2drasta.com chmod -Rv og+w /srv/liquuidme/wp-content/uploads || echo ok
