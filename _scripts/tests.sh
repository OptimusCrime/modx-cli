#!/bin/bash

php vendor/bin/phpcs
php vendor/bin/phpmd ./src text phpmd
