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

So I'll show you how to go about this using the GitLab CI/CD Tools. This could work with every other CI/CD tool with a little tweak and I'll reference other CI/CD Tools like GitHub Actions, Travis, etc. Where necessary changes can be made to fit in to other CI/CD tools. 
