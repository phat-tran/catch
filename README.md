# Catch.com.au Test Task

## Disclaimer
The Docker setup template is fully credited to https://github.com/dunglas/symfony-docker
\
The Geolocation feature uses https://www.openstreetmap.org/ API.

## Dependencies

- `symfony/yaml`: to work with Yaml output format.
- `ext-simplexml`: to work with Xml output format.
- `symfony/mailer`: to work with sending email. Also need a working Gmail's email and password.
- `symfony/http-client`: to get geolocation data from OpenStreetMap.

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Copy `.env-example` to `.env`.
    1. Update `MAILER_DSN` setting inside .env with your Gmail username and password for the sending email feature to work.
3. Run `HTTP_PORT=8000 HTTPS_PORT=4443 HTTP3_PORT=4443 docker-compose up --build -d` to build and run Symfony app.
4. Run `docker-compose exec php composer update` to install required dependencies.

## Run Command

4. Run `docker-compose exec php php bin/console app:process-orders` to generate report.
   1. Options:
      1. `--filename`: (Optional) The name of the output file. Default is `out`. Only alphanumeric allowed.
      2. `--type`: (Optional) The output format for the output file. Default is `csv`.
      3. `--email-to`: (Optional) The recipient's email addresses. E.g person1@catch.com.au,person2@catch2.com.au.
      4. `--geolocation`: (Optional) Flag to trigger fetching shipping address geolocation (lat and lon). 1 means yes. 0 means no. If yes, the process will be slower as it needs to fetch geolocation from the API.
   2. Examples:
      1. `docker-compose exec php php bin/console app:process-orders --type=json` will generate report as JSON format.
      1. `docker-compose exec php php bin/console app:process-orders --email-to=person1@catch.com.au` will generate report as CSV format and attach the report and send email to person1@catch.com.au.
      
## Work with the code

#### To add more command's options.

1. Add more options in `src/Command/ProcessOrderCommand.php`. `ProcessOrderCommand->configure()`.
2. Add validation for new options.

#### To add new output format.
1. Add the new format to `enum OutputType` at `src/Service/Enums/OutputType.php`. E.g., `case PDF = 'pdf'`;
2. Create new subclass of `Output` in `src/Service/Output`. E.g., `class OutputPdf extends Output`.
3. Implement all the required abstract methods. 

Each subclass can handle itself how to generate the report.
- If the format wants line by line feature: 
   1. Line by line feature means the process will read each line of the input file, generate the report, immediately write the output line to the output file.
   2. To enable line by line feature. Set `protected bool $writeEachLine = true;` in the subclass.

Currently only `csv` and `jsonl` formats support line by line feature.
