**Consignes pour exécuter le back**

Pour exécuter le projet il faut : 
  1. Faire un **composer install**
  2.  Créer des clés ssh privés/public (pour l'utilisation de JWT) :
    -> **openssl genrsa -out private_key.pem 4096**
    -> **openssl rsa -in private_key.pem -pubout -out public_key.pem**
    -> **ssh-keygen -t rsa -b 4096**
  3. Renseigner les clés dans le yaml :
    -> **lexik_jwt_authentication:
    secret_key:   '%kernel.project_dir%/private_key.pem'
    public_key:   '%kernel.project_dir%/public_key.pem'
    pass_phrase:  '%env(JWT_PASSPHRASE)%' 
    token_ttl:    3600**
  4. Créer la base :
     -> **php bin/console doctrine:database:create**
  5. Créer les migrations :
     -> **php bin/console make:migration**
  6. Jouer les migrations :
     -> **php bin/console doctrine:migrations:migrate**
  7. Jouer les fixtures pour avoir le jeu de données :
     -> **php bin/console doctrine:fixtures:load --no-interaction**
  8. Lancer le serveur :
     -> **symfony server:start**



  
