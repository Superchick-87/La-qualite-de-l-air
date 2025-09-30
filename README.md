## Affiche la qualité de l'air et des pollens à J +1 

Récupération des données (après 14h) :</br>
"get_datas.php"</br>
(Une prévision de l’indice ATMO du jour et du lendemain est publiée quotidiennement à 14h00.)</br>

### Organisation des fichiers
```text
.
  ├── datas/
  │   └── nouvelle_aquitaine_demain_YYYY-MM-DD.json
  ├── includes/
  │   ├── pdf_3cols2M.php
  │   └── send_mail.php
  └── index.php
  └── get_datas.php
