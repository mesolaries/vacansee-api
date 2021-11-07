# Vacansee API

Vacansee API scrapes vacancies from different job listing websites of Azerbaijan and creates universal database with API access. 

Supported websites:

- [x] https://boss.az
- [x] https://www.hellojob.az
- [x] https://jobsearch.az
- [x] https://www.offer.az
- [x] https://projobs.az
- [x] https://www.rabota.az

## Deploying

Clone the repository, then install dependencies via composer:

```bash
$ composer install
```

Install javascript dependencies:

```bash
$ yarn install
```

Create `.env.local` file or modify `.env` file. Set `DATABASE_URL`, `MAILER_URL` and `MAILER_DSN` environment variables.

Create database and run migrations:

```bash
$ php bin/console doctrine:database:create
$ php bin/console doctrine:migrations:migrate
```

Sync categories with database (you have to do this every time you add new category):

```bash
$ php bin/console app:sync:categories
```

Done!

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](LICENSE)
