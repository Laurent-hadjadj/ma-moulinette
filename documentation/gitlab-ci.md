# Intégration Continue

Le plus important dans l'utilisation de SonarQube ce n'est pas les indicateurs en eux-mêmes mais plutôt la tendance de votre projet. En effet l'idée derrière des outils tels que SonarQube c'est d'analyser de manière continue afin d'améliorer de manière continue votre application.

Cette partie est issue de la documentation de SonarQube.

* Changer la projectKey par une valeur pour vous.
* http(s)://ip.ou.domaine.vers.votre.sonar par le lien vers votre SonarQube.
* VOTRE-TOKEN par le token obtenu à l'étape précédente

```txt
sonarqube-check:
  stage: test
  image:
    name: sonarsource/sonar-scanner-cli:latest
    entrypoint: [""]
  variables:
    SONAR_USER_HOME: "${CI_PROJECT_DIR}/.sonar"  # Defines the location of the analysis task cache
    GIT_DEPTH: "0"  # Tells git to fetch all the branches of the project, required by the analysis task
  cache:
    key: "${CI_JOB_NAME}"
    paths:
      - .sonar/cache
  script:
    - sonar-scanner -Dsonar.qualitygate.wait=true -Dsonar.projectKey=vitejs-sample -Dsonar.sources=. -Dsonar.host.url=http(s)://ip.ou.domaine.vers.votre.sonar -Dsonar.login=VOTRE-TOKEN
  allow_failure: true
  only:
    - master
```

docker

```plaintext
- name: Setup PHP with Xdebug
    uses: shivammathur/setup-php@v2
    with:
      php-version: '8.1'
      coverage: xdebug

- name: Install dependencies with composer
    run: composer update --no-ansi --no-interaction --no-progress

- name: Run tests with phpunit/phpunit
    run: vendor/bin/phpunit --coverage-clover=coverage.xml
```

```plaintext
name: build
on:
  - pull_request
  - push
jobs:
  tests:
      name: Tests
      runs-on: ubuntu-latest
      steps:
        - name: Checkout
          uses: actions/checkout@v2
          with:
            fetch-depth: 0
        - name: Setup PHP with Xdebug
          uses: shivammathur/setup-php@v2
          with:
            php-version: '8.1'
            coverage: xdebug
        - name: Install dependencies with composer
          run: composer update --no-ansi --no-interaction --no-progress
        - name: Run tests with phpunit/phpunit
          run: vendor/bin/phpunit --coverage-clover=coverage.xml
        - name: SonarQube Scan
          uses: SonarSource/sonarqube-scan-action@master
          env:
            SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
            SONAR_HOST_URL: ${{ secrets.SONAR_HOST_URL }}
```
