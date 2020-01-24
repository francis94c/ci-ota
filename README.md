# ci-ota

A Code Igniter Continuous Delivery Tool.

CI-OTA is a Code Igniter library that allows you generates file patches based on Git change sets between two commit Shas, Which can be used to patch a code Igniter distribution (Probably in production). A solution for continuous integration and delivery for Code Igniter applications.

### Installation ###

Download and Install Splint from https://splint.cynobit.com/downloads/splint and run the below from the root of your Code Igniter project.
```bash
splint install francis94c/ci-ota
```

## How It Works ##

Let's say you have two copies of a particular Code Igniter project, One in your System (Local Development Environment) and one in your Remote Server (Production Environment). And you have the `ci-ota` library installed in both of them.

We'd basically want to build a patch as an archive(zip) of changed files between two commit SHAs from the development version of our project and send to the production version of our server to patch the necessary (updated) files.

We'd also want this to be done automatically using a CI/CD Pipeline.

So I'll show you how to go about this using the GitHub Actions CI/CD Tools. This could work with every other CI/CD Tools like Travis, etc. with little difference which I'll point out along the way.

Below is our workflow `.yml` file contained in `.github/workflows/deploy.yml` Which tells GitHub to deploy our application whenever we make a release.

__NOTE:__ This tutorial assumes you know about and how to use [Splint](https://splint.cynobit.com). If you don't, you can still proceed as i'll explain as much as I can about Splint where necessary.

```yml
name: Deploy To Production

on:
  release:
    branches:
      - master

jobs:
  build:

    runs-on: ubuntu-latest

    steps:
    - name: Checkout
      uses: actions/checkout@v1

    - name: Setup PHP
      uses: shivammathur/setup-php@v1
      with:
        php-version: '7.3'
        extensions: mbstring, intl pdo_mysql
        ini-values: post_max_size=256M, short_open_tag=On

    - name: Install Splint Packages
      run: php ./splint.php

    - name: Build and Deploy Patch
      run: php index.php CLIController BuildAndDeploy ${{ secrets.DEPLOY_URL }} ${{ secrets.DEPLOY_SECRET }} ${{ secrets.DEPLOY_SECRET_ALGORITHM }}

    - name: Setup Git Config
      run: |
        git add splint.json
        git config --global user.email "info@bot.com"
        git config --global user.name "CI Release Bot"
        git commit -m "Update Deploy Verison SHA"

    - name: Push changes
      uses: ad-m/github-push-action@master
      with:
        github_token: ${{ secrets.GITHUB_TOKEN }}
```

Let's go over the `.yml` file.
