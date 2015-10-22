config_files := \
	test/ccouch-repos.lst \
	test/dbc.json

generated_resources := \
	test/db-scripts/create-tables.sql \
	test/db-scripts/create-database.sql \
	test/db-scripts/drop-database.sql \
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
	create-database \
	default \
	drop-database \
	everything \
	empty-database \
	rebuild-database \
	resources \
	run-tests \
	run-unit-tests \
	run-web-server \
	upgrade-database \
	clean \
	everything

build-resources: ${build_resources}
runtime-resources: ${runtime_resources}
resources: ${resources}

clean:
	rm -rf ${generated_resources}

vendor: composer.json
	rm composer.lock
	composer install
	touch "$@"

${config_files}: %: | %.example
	cp "$|" "$@"

util/phpstoragetest-psql: test/dbc.json
	vendor/bin/generate-psql-script -psql-exe psql "$<" >"$@"
	chmod +x "$@"
util/phpstoragetest-pg_dump: test/dbc.json
	vendor/bin/generate-psql-script -psql-exe pg_dump "$<" >"$@"
	chmod +x "$@"

util/SchemaSchemaDemo.jar: \
%: %.urn | vendor test/ccouch-repos.lst
	${fetch} -o "$@" `cat "$<"`

test/db-scripts/create-tables.sql: test/schema.txt util/SchemaSchemaDemo.jar
	${schemaschemademo} -o-create-tables-script "$@"

test/schema.php: test/schema.txt util/SchemaSchemaDemo.jar
	${schemaschemademo} -o-schema-php "$@" -php-schema-class-namespace EarthIT_Schema

test/db-scripts/create-database.sql: test/dbc.json vendor
	mkdir -p test/db-scripts
	vendor/bin/generate-create-database-sql "$<" >"$@"
test/db-scripts/drop-database.sql: test/dbc.json vendor
	mkdir -p test/db-scripts
	vendor/bin/generate-drop-database-sql "$<" >"$@"

#www/images/head.png:
#	${fetch} -o "$@" "urn:bitprint:HYWPXT25DHVRV4BXETMRZQY26E6AQCYW.33QDQ443KBXZB5F5UGYODRN2Y34DOZ4GILDI7ZA"

create-database drop-database: %: test/db-scripts/%.sql
	sudo su postgres -c "cat '$<' | psql"

empty-database: test/db-scripts/empty-database.sql util/phpstoragetest-psql
	util/phpstoragetest-psql <"$<"

upgrade-database: test/db-scripts/create-schema.sql test/db-scripts/create-tables.sql util/phpstoragetest-psql
	util/phpstoragetest-psql <test/db-scripts/create-schema.sql
	util/phpstoragetest-psql <test/db-scripts/create-tables.sql

rebuild-database: empty-database upgrade-database

run-unit-tests: runtime-resources upgrade-database
	vendor/bin/phpunit --bootstrap test/phpuinit-bootstrap.php test

run-tests: run-unit-tests
