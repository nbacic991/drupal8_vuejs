## Step by step how to initialise project

When you clone this repo to your local machine, just follow this steps and you will get your Drupal 8 (back-end) connected with VueJS (front-end). 

If you don't have vue-cli installed globally type in the following:

    npm i -g vue-cli

### Step 1

Enter your folder where you've cloned this repo and type:
    
    docker-compose up -d
    

### Step 2

Open your favourite browser and in your URL bar type: 

    drupal8vue.dev.loc
    

### Step3

When your container is up, you have to enter your php shell and import database. Open your terminal and type in:

    1. docker-compose exec --user 82 php sh
    2. drush sql-cli < ../databases/movies.sql
    
### Step 4 

In Step 3, you have imported database with Movies Content-Type and 3 Movies created.

Now it's VueJS time !

VueJS is already installed in this repo, so the only thing you have to do is to enter VueJS folder, find package.json file and click "npm install".

### Step 5

When VueJS finishes installing dependencies, it's time to run it.

Open your terminal, navigate to folder ( if you aren't there ) where you've cloned this repo, enter VueJS folder and type in the following:

    npm run dev
    
After npm runs dev server, open your browser and in your URL bar type:

    localhost:8081/
    
    
### Step 6

That it's it. Enyoj.