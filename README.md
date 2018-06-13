# OpenCart Project Template

## Getting Started

 1. Create a new project: `composer create-project beyondit/opencart-project-template ./my/project/folder -s dev`
 2. Copy the `.env.sample` file to `.env` and set the configuration parameters respectively
 3. Run `bin/robo opencart:setup` and afterwards `bin/robo opencart:run` on command line (`bin/robo opencart:run &` to run in background)
 4. Open `http://localhost:8000` in your browser

## Robo Commands

 * `bin/robo opencart:setup` : Install OpenCart with configuration set in `.env` file
 * `bin/robo opencart:run`   : Run OpenCart on a php build-in web server on port 8000
 * `bin/robo project:deploy` : Mirror contents of the src folder to the OpenCart test environment
 * `bin/robo project:watch`  : Redeploy after changes inside the src/ folder or the composer.json file
 * `bin/robo project:package`: Package a `build.ocmod.zip` inside the target/ folder
 
## Writing Tests
 
 * Based on the [OpenCart Testing Suite](https://github.com/beyondit/opencart-test-suite) project tests can be written.
 * After successful setup and deployment, tests can be executed by running the `bin/phpunit` command.
 * Two examples inside the `/tests` folder are given, which can be executed as separat Testsuites by `bin/phpunit --testsuite admin-tests` or `bin/phpunit --testsuite catalog-tests`




