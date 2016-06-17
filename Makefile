config_files := \
	test/ccouch-repos.lst \
	test/dbc-mysql.json \
	test/dbc-postgres.json

generated_resources := \
	test/postgres-scripts/create-tables.sql \
	test/postgres-scripts/create-database.sql \
	test/postgres-scripts/drop-database.sql \
	util/phpstoragetest-psql \
	util/phpstoragetest-pg_dump \
	util/SchemaSchemaDemo.jar \
	test/schema.php \
	vendor

build_resources := ${generated_resources} ${config_files}

runtime_resources := \
	test/schema.php \
	vendor

resources := ${build_resources} ${runtime_resources}

schemaschemademo := java -jar util/SchemaSchemaDemo.jar test/schema.txt

fetch := vendor/bin/fetch -repo @test/ccouch-repos.lst

default: resources run-tests

.DELETE_ON_ERROR:

.PHONY: \
	create-postgres-database \
	default \
	drop-postgres-database \
	everything \
	empty-postgres-database \
	rebuild-postgres-database \
	resources \
	run-tests \
	run-unit-tests \
	run-web-server \
	upgrade-postgres-database \
	clean \
	everything

build-resources: ${build_resources}
runtime-resources: ${runtime_resources}
resources: ${resources}

clean:
	rm -rf ${generated_resources}

vendor: composer.json
	rm -f composer.lock
	composer install
	touch "$@"

${config_files}: %: | %.example
	cp "$|" "$@"

util/phpstoragetest-psql: test/dbc-postgres.json
	vendor/bin/generate-psql-script -psql-exe psql "$<" >"$@"
	chmod +x "$@"
util/phpstoragetest-pg_dump: test/dbc-postgres.json
	vendor/bin/generate-psql-script -psql-exe pg_dump "$<" >"$@"
	chmod +x "$@"

util/SchemaSchemaDemo.jar: \
%: %.urn | vendor test/ccouch-repos.lst
	${fetch} -o "$@" `cat "$<"`

test/postgres-scripts/create-tables.sql: test/schema.txt util/SchemaSchemaDemo.jar
	${schemaschemademo} -o-create-tables-script "$@"

test/schema.php: test/schema.txt util/SchemaSchemaDemo.jar
	${schemaschemademo} -o-schema-php "$@" -php-schema-class-namespace EarthIT_Schema

test/postgres-scripts/create-database.sql: test/dbc-postgres.json vendor
	mkdir -p test/postgres-scripts
	vendor/bin/generate-create-database-sql "$<" >"$@"
test/postgres-scripts/drop-database.sql: test/dbc-postgres.json vendor
	mkdir -p test/postgres-scripts
	vendor/bin/generate-drop-database-sql "$<" >"$@"

create-mysql-database: %: test/mysql-scripts/%.sql
	cat '$<' | mysql -uroot

create-postgres-database: test/postgres-scripts/create-database.sql
	cat '$<' | sudo -u postgres psql -v ON_ERROR_STOP=1
	sudo -u postgres psql -v ON_ERROR_STOP=1 phpstoragetest <test/postgres-scripts/create-postgis-extension.sql
drop-postgres-database: test/postgres-scripts/drop-database.sql
	cat '$<' | sudo -u postgres psql -v ON_ERROR_STOP=1

empty-postgres-database: test/postgres-scripts/empty-database.sql util/phpstoragetest-psql
	util/phpstoragetest-psql <"$<"

upgrade-postgres-database: \
		test/postgres-scripts/drop-schema.sql \
		test/postgres-scripts/create-schema.sql \
		test/postgres-scripts/create-tables.sql \
		util/phpstoragetest-psql
	util/phpstoragetest-psql -v ON_ERROR_STOP=1 <test/postgres-scripts/drop-schema.sql
	util/phpstoragetest-psql -v ON_ERROR_STOP=1 <test/postgres-scripts/create-schema.sql
	util/phpstoragetest-psql -v ON_ERROR_STOP=1 <test/postgres-scripts/create-tables.sql

rebuild-postgres-database: empty-postgres-database upgrade-postgres-database

run-unit-tests: runtime-resources upgrade-postgres-database
	vendor/bin/phpunit --bootstrap test/phpuinit-bootstrap.php test

run-tests: run-unit-tests
