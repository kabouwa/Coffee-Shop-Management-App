# CDC — Coffee Shop Management SaaS

## 1. Présentation du projet

Le projet consiste à développer une application web **SaaS de gestion de coffee shops**, permettant à chaque gérant de gérer son propre établissement à partir d'une interface web.

### Technologies

- **Backend :** Laravel
- **Frontend :** React.js
- **Base de données :** MySQL
- **API :** REST API
- **Authentification :** Laravel Sanctum
- **UI :** Tailwind CSS / Bootstrap

L'application devra être **multi-tenant** : chaque utilisateur possède ses propres produits et commandes, qui ne doivent jamais être accessibles aux autres utilisateurs.

---

## 2. Objectifs

L'application doit permettre à un gérant de :

- créer et gérer son compte, ainsi que la fiche de son coffee shop (nom, adresse) ;
- gérer ses produits ;
- créer et gérer ses commandes ;
- consulter l'historique des commandes ;
- suivre les statistiques de son coffee shop ;
- consulter un dashboard synthétique ;
- accéder uniquement à ses propres données.

L'objectif technique est de mettre en pratique une architecture **Full Stack séparant React et Laravel via une API REST**.

---

## 3. Acteurs

### Gestionnaire

Le gestionnaire est l'utilisateur principal de l'application.

Il peut :

- s'inscrire, en renseignant à la fois ses informations de compte et les informations de son coffee shop ;
- se connecter ;
- se déconnecter ;
- gérer les informations de son coffee shop (nom, adresse) ;
- gérer ses produits ;
- gérer ses commandes ;
- consulter les statistiques de son coffee shop.

### Système

Le système est responsable de :

- l'authentification ;
- l'autorisation ;
- la séparation des données entre utilisateurs ;
- le calcul des statistiques ;
- la validation des données.

---

## 4. Authentification

### Inscription

Un utilisateur peut créer un compte avec :

**Informations du compte**

- Nom
- Email
- Mot de passe
- Confirmation du mot de passe

**Informations du coffee shop**

À l'inscription, l'utilisateur renseigne également les informations de son établissement, créées en même temps que son compte :

- Nom du coffee shop (`shop_name`)
- Adresse (`address`)
- Code postal (`zipcode`)
- Ville (`city`)
- Pays (`country`)

La création du compte et la création de la fiche coffee shop associée doivent être traitées comme une seule opération atomique : si l'une échoue, l'autre ne doit pas être conservée.

### Connexion

L'utilisateur peut se connecter avec :

- Email
- Mot de passe

Après authentification, il reçoit une session/token lui permettant d'accéder aux ressources protégées.

### Déconnexion

L'utilisateur peut se déconnecter et invalider son authentification.

### Protection

Les ressources privées doivent être protégées.

Un utilisateur ne doit **jamais pouvoir accéder aux données d'un autre utilisateur**, même en connaissant l'ID d'une ressource. Cela inclut la fiche coffee shop, qui n'est visible et modifiable que par son propriétaire.

---

## 5. Gestion du coffee shop (profil)

Chaque utilisateur possède une fiche coffee shop unique, créée à l'inscription.

### Données du coffee shop

| Champ | Description |
|---|---|
| id | Identifiant |
| user_id | Propriétaire |
| shop_name | Nom du coffee shop |
| address | Adresse |
| zipcode | Code postal |
| city | Ville |
| country | Pays |
| created_at | Date de création |
| updated_at | Date de modification |

### Fonctionnalités

Le gestionnaire peut :

- consulter les informations de son coffee shop ;
- modifier les informations de son coffee shop (nom, adresse, code postal, ville, pays).

---

## 6. Gestion des produits

Chaque utilisateur possède ses propres produits.

### Données d'un produit

| Champ | Description |
|---|---|
| id | Identifiant |
| user_id | Propriétaire |
| name | Nom du produit |
| description | Description |
| price | Prix |
| category | Catégorie |
| image | Image facultative |
| available | Disponibilité |
| created_at | Date de création |
| updated_at | Date de modification |

### Fonctionnalités

Le gestionnaire peut :

- afficher ses produits ;
- créer un produit ;
- consulter un produit ;
- modifier un produit ;
- supprimer un produit ;
- activer/désactiver un produit ;
- rechercher un produit ;
- filtrer les produits par catégorie.

---

## 7. Gestion des commandes

Le gestionnaire peut gérer les commandes de son coffee shop.

### Données d'une commande

| Champ | Description |
|---|---|
| id | Identifiant |
| user_id | Propriétaire |
| customer_name | Nom du client |
| status | Statut |
| total | Total |
| created_at | Date |
| updated_at | Date de modification |

Une commande possède plusieurs produits via une table `order_items`.

### Order Item

| Champ | Description |
|---|---|
| id | Identifiant |
| order_id | Commande |
| product_id | Produit |
| quantity | Quantité |
| unit_price | Prix au moment de la commande |
| subtotal | Sous-total |

Le `unit_price` est sauvegardé dans `order_items` afin de conserver l'historique correct même si le prix du produit est ensuite modifié.

---

## 8. CRUD des commandes

Le gestionnaire peut :

- créer une commande ;
- consulter une commande ;
- modifier une commande ;
- supprimer une commande ;
- consulter la liste de ses commandes ;
- rechercher une commande ;
- filtrer les commandes par statut ;
- filtrer les commandes par date.

### Statuts

- `Pending`
- `Preparing`
- `Ready`
- `Completed`
- `Cancelled`

---

## 9. Dashboard

Après connexion, le gestionnaire arrive sur un dashboard contenant les principales statistiques de son coffee shop.

### Statistiques principales

- chiffre d'affaires total ;
- nombre total de commandes ;
- commandes du jour ;
- commandes en cours ;
- commandes terminées ;
- produit le plus vendu ;
- panier moyen.

### Graphiques

Le dashboard peut afficher :

- chiffre d'affaires par jour, semaine ou mois ;
- produits les plus vendus ;
- répartition des commandes par statut.

Toutes les statistiques doivent être calculées **uniquement à partir des données de l'utilisateur connecté**.

---

## 10. Architecture SaaS / Multi-tenant

Chaque donnée métier doit appartenir à un utilisateur.

### Relations

```text
User
 ├── hasOne Shop
 ├── hasMany Products
 └── hasMany Orders
             └── hasMany OrderItems
                         └── belongsTo Product
```

Structure logique :

```text
users
  │
  ├──────── shop
  │
  ├──────── products
  │
  └──────── orders
                │
                └──── order_items
```

Chaque requête du backend doit vérifier la propriété de la ressource.

Un utilisateur ne doit jamais pouvoir récupérer, modifier ou supprimer une ressource appartenant à un autre utilisateur — y compris la fiche `shop` d'un autre gérant.

---

## 11. API REST

Laravel fournira une API consommée par React.

### Authentication

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/user
```

`POST /api/auth/register` crée à la fois l'utilisateur et sa fiche `shop` associée en une seule requête.

### Profile / Shop

```text
GET /api/auth/user       (renvoie l'utilisateur avec sa relation shop chargée)
PUT /api/auth/profile    (met à jour les infos du compte ET du shop)
```

### Products

```text
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
```

### Orders

```text
GET    /api/orders
POST   /api/orders
GET    /api/orders/{id}
PUT    /api/orders/{id}
DELETE /api/orders/{id}
```

### Dashboard

```text
GET /api/dashboard
```

---

## 12. Interface React

### Pages publiques

```text
/login
/register
```

Le formulaire `/register` comporte deux groupes de champs : les informations de compte (nom, email, mot de passe, confirmation) et les informations du coffee shop (nom du shop, adresse, code postal, ville, pays), soumis en une seule requête.

### Pages privées

```text
/dashboard
/products
/products/create
/products/{id}
/products/{id}/edit
/orders
/orders/create
/orders/{id}
/orders/{id}/edit
/profile
```

La page `/profile` permet de modifier à la fois les informations du compte et celles du coffee shop.

### Layout

```text
┌─────────────────────────────────────────────┐
│ Header                                      │
├────────────┬────────────────────────────────┤
│ Sidebar    │ Content                        │
│            │                                │
│ Dashboard  │                                │
│ Products   │                                │
│ Orders     │                                │
│ Profile    │                                │
│ Logout     │                                │
└────────────┴────────────────────────────────┘
```

---

## 13. Sécurité

L'application devra assurer :

- authentification obligatoire pour les ressources privées ;
- validation côté Laravel ;
- autorisation des ressources ;
- hashage des mots de passe ;
- isolation des données entre utilisateurs (produits, commandes, et fiche shop) ;
- validation des données reçues par l'API ;
- protection des endpoints sensibles ;
- gestion correcte des erreurs HTTP ;
- atomicité de la création compte + shop à l'inscription (transaction DB).

---

## 14. Base de données

### users

```text
id
name
email
password
timestamps
```

### shops

```text
id
user_id
shop_name
address
zipcode
city
country
timestamps
```

### products

```text
id
user_id
name
description
price
category
image
available
timestamps
```

### orders

```text
id
user_id
customer_name
status
total
timestamps
```

### order_items

```text
id
order_id
product_id
quantity
unit_price
subtotal
timestamps
```

### Relations

```text
User 1 ─────── 1 Shop

User 1 ─────── N Product

User 1 ─────── N Order

Order 1 ────── N OrderItem

Product 1 ──── N OrderItem
```

---

## 15. Fonctionnalités hors périmètre

Pour garder le projet petit et réalisable, la première version ne prévoit pas :

- paiement en ligne ;
- livraison ;
- gestion des employés ;
- gestion de stock avancée ;
- notifications SMS ;
- application mobile ;
- système de fidélité ;
- abonnement SaaS payant ;
- multi-branches pour un même compte.

Ces fonctionnalités pourront être ajoutées dans une version future.

---

## 16. MVP

### Authentification

- Register (compte + shop en une seule opération)
- Login
- Logout
- Authenticated user

### Coffee shop (profil)

- Read
- Update

### Produits

- List
- Create
- Read
- Update
- Delete

### Commandes

- List
- Create
- Read
- Update
- Delete

### Dashboard

- Total revenue
- Total orders
- Today's orders
- Pending orders
- Best-selling products
- Revenue chart

### SaaS isolation

Les produits, les commandes et la fiche coffee shop doivent être strictement isolés par utilisateur.

---

## 17. Résultat attendu

À la fin du projet, l'application doit permettre à plusieurs coffee shops de partager **la même application**, tout en conservant leurs données complètement séparées.

```text
                 Coffee Shop SaaS
                       │
          ┌────────────┴────────────┐
          │                         │
       Manager A                 Manager B
          │                         │
     ┌────┼────┬─────┐         ┌────┼────┬─────┐
     │    │    │     │         │    │    │     │
   Shop Products Orders        Shop Products Orders
     │    │    │     │         │    │    │     │
     └────┴────┴──┬──┘         └────┴────┴──┬──┘
                  │                          │
              Dashboard                  Dashboard
```

Le projet permet ainsi de pratiquer :

- Laravel ;
- React ;
- REST API ;
- authentification ;
- CRUD ;
- relations Eloquent (1-1 et 1-N) ;
- autorisation ;
- MySQL ;
- architecture Full Stack ;
- multi-tenancy / SaaS.