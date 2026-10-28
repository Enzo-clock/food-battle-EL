# FoodBattle

FoodBattle est une application web de duel culinaire. Elle permet de parcourir et de voter pour des plats, de créer des duels et des tournois, et de consulter des classements. Les utilisateurs peuvent aussi proposer des plats, des restaurants et des catégories. Les propositions sont soumises à la modération d’un administrateur.

## Prérequis

- Docker avec Docker Compose
- Une connexion Internet pour charger les photographies d’illustration hébergées par Pexels

## Lancement local

Si nécessaire, créez le fichier `.env` à partir de `.env.example`, puis démarrez l’application :

```bash
docker compose up --build
```

Ouvrez ensuite [http://localhost:8080](http://localhost:8080).

Pour arrêter les services :

```bash
docker compose down
```

Cette commande conserve les données de la base. **N’ajoutez pas `-v`**, sauf si vous souhaitez supprimer le volume Docker et toutes les données qu’il contient.

## Base de données

L’application utilise PostgreSQL. Les données persistent dans le volume Docker `foodbattle_data`.

Les scripts du dossier `migrations/` sont exécutés automatiquement uniquement lors de l’initialisation d’une base vide. Pour appliquer une nouvelle migration à une base déjà créée, exécutez-la manuellement. Par exemple, dans PowerShell :

```powershell
Get-Content -Raw migrations/005_tournament_runs.sql | docker compose exec -T db psql -U foodbattle -d foodbattle
```

Chaque évolution du schéma doit être ajoutée dans un nouveau fichier de migration numéroté. Ne modifiez pas `migrations/init.sql` pour faire évoluer une base existante.

Pour ouvrir une session SQL dans le terminal :

```bash
docker compose exec db psql -U foodbattle -d foodbattle
```

## Administration

Pour créer le premier administrateur, remplacez l’adresse par celle du compte concerné :

```bash
docker compose exec app php bin/make-admin.php email@exemple.fr
```

L’interface d’administration est accessible à l’adresse `/admin` et réservée aux administrateurs.

## Interface de gestion de la base (optionnelle)

Adminer peut être démarré avec le profil `adminer` :

```bash
docker compose --profile adminer up --build
```

Ouvrez [http://localhost:8081](http://localhost:8081), puis utilisez les paramètres suivants :

| Champ | Valeur |
| --- | --- |
| Système | PostgreSQL |
| Serveur | `db` |
| Utilisateur | `foodbattle` |
| Mot de passe | `foodbattle_local` |
| Base de données | `foodbattle` |

Adminer est prévu pour le développement local : n’exposez pas son port en production.

## Routes de l’application

Les méthodes indiquées sont celles acceptées par les routes. Les paramètres entre accolades sont dynamiques : `{id}` correspond à l’identifiant d’une ressource. Sauf indication contraire, les routes de création et de modification nécessitent un compte connecté.

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/` | Page d’accueil |
| GET | `/duel` | Affichage d’un duel |
| POST | `/duel/vote` | Voter pour un plat dans un duel |
| GET, POST | `/signup` | Inscription |
| GET, POST | `/login` | Connexion |
| POST | `/logout` | Déconnexion |
| GET, POST | `/restaurant/creer` | Proposer un restaurant |
| GET, POST | `/plat/creer` | Proposer un plat |
| GET, POST | `/categorie/creer` | Proposer une catégorie |
| GET, POST | `/duel/creer` | Créer un duel |
| GET, POST | `/tournoi/creer` | Créer un tournoi |
| GET | `/dashboard` | Tableau de bord |
| GET | `/classements` | Classements |
| GET | `/play/tournament` | Liste ou accueil des tournois à jouer |
| GET | `/play/tournament/{id}/{round}` | Afficher un match de tournoi |
| POST | `/play/tournament/{id}/{round}/vote` | Voter dans un match de tournoi |
| GET | `/mentions-legales` | Mentions légales |
| GET | `/confidentialite` | Politique de confidentialité |
| GET | `/contact` | Page de contact |
| GET | `/admin` | Tableau de bord d’administration |
| POST | `/admin/{type}/{id}/{action}` | Valider ou refuser une proposition (`type` : `plats`, `restaurants` ou `categories` ; `action` : `valider` ou `refuser`) |
| POST | `/admin/utilisateurs/{id}/role` | Modifier le rôle d’un utilisateur |

Pour les actions sur une ressource existante, les routes suivantes sont également disponibles :

| Méthode | Route | Description |
| --- | --- | --- |
| GET, POST | `/plat/{id}/modifier` | Modifier un plat |
| GET, POST | `/plat/{id}/supprimer` | Supprimer un plat |
| POST | `/plat/{id}/restaurer` | Restaurer un plat archivé |
| GET, POST | `/restaurant/{id}/modifier` | Modifier un restaurant |
| GET, POST | `/restaurant/{id}/supprimer` | Supprimer un restaurant |
| GET, POST | `/categorie/{id}/modifier` | Modifier une catégorie |
| GET, POST | `/categorie/{id}/supprimer` | Supprimer une catégorie |
| GET, POST | `/duel/{id}/supprimer` | Supprimer un duel |
| GET, POST | `/tournoi/{id}/modifier` | Modifier un tournoi |
| GET, POST | `/tournoi/{id}/supprimer` | Supprimer un tournoi |

Pour les actions de modification et de suppression, `GET` affiche l’écran de confirmation ou le formulaire ; `POST` effectue l’action. La restauration d’un plat s’effectue directement en `POST`. Les routes sous `/admin` sont réservées aux administrateurs.

Dans les routes de tournoi, `{round}` peut être `Q1`, `Q2`, `Q3`, `Q4`, `D1`, `D2` ou `F`.

## Données et images

Les données fournies par le projet sont fictives. Les photographies d’illustration sont servies par Pexels et nécessitent une connexion Internet.
