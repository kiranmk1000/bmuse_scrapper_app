<p align="center">Web Scrapping with Laravel</p>

## About Project

This will scrape the data from [this](https://jameelcast.pinecast.co/) website. It will scrape the follwing data and saved to database.
- Title
- Episode notes
- Image URL
- Audio URL.

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
- [Documentation](#documentation)

## Installation

Please clone the project to your local directory.

```bash
git clone https://github.com/kiranmk1000/bmuse_scrapper_app.git
```
Create a new database and configure it inside config/database.php and .env file. Run the below artisan command. 
Open command prompt and run the following commands.

```bash
cd bmuse_scrapper_app
composer install
php artisan migrate
```
## Usage

Run the following artisan command to scrape data and save to database.
```
php artisan scrape:data
```
Run the following command to run the application
```
php artisan serve
```
Open Postman, run the GET request below to view the scrapped data along with pagination. 
```
GET /api/scrape_data
# To get the the paginated data, run the following request with page number
GET /api/scrape_data?page=2
```
## Documentation

I have used [symfony/dom-crawler](https://packagist.org/packages/symfony/dom-crawler) package for scrape the data from website.
New artisan command created to fetch the data and save it to database.
```
php artisan scrape:data
```
