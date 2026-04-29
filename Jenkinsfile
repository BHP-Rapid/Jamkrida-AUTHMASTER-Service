pipeline {
    options {
        skipDefaultCheckout(true)  // <-- ini penting
    }
    agent any
    
    environment {
        GIT_CREDENTIALS = credentials('github_org_pat')
        DOCKER_CMD  = "sudo docker"
    }

    stages {
        stage('Checkout') {
            steps {
                cleanWs()
                checkout scm
            }
        }

        stage('Rebuild & Run Containers') {
            steps {
                sh '''
                    cp ".env.demo" ".env"
                    ${DOCKER_CMD} compose down
                    ${DOCKER_CMD} compose up -d --build app nginx
                '''
            }
        }

        stage('Setup & Deploy') {
            steps {
                sh '''
                    ${DOCKER_CMD} exec -u root jjkt-auth bash -c "
                    chown -R www-data:www-data /var/www &&
                    chmod -R 775 /var/www
                    "

                    ${DOCKER_CMD} exec jjkt-auth composer install --no-interaction --prefer-dist --optimize-autoloader
                    ${DOCKER_CMD} exec jjkt-auth composer update
                    ${DOCKER_CMD} exec jjkt-auth php artisan config:cache
                    ${DOCKER_CMD} exec jjkt-auth php artisan queue:work --daemon --quiet --sleep=3 --tries=3 &
                    
                    ${DOCKER_CMD} exec jjkt-auth chmod -R 775 storage bootstrap/cache 
                    ${DOCKER_CMD} exec jjkt-auth chown -R www-data:www-data storage bootstrap/cache 
                '''
            }
        }

        stage('Install Dependencies') {
            steps {
                sh '''
                ${DOCKER_CMD} exec jjkt-auth bash -c "
                COMPOSER_MEMORY_LIMIT=512M \
                ${DOCKER_CMD} install --no-dev --optimize-autoloader --no-interaction --prefer-dist
                "
                '''
            }
        }
    }
}
