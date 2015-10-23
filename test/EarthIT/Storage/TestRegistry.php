<?php

class EarthIT_Storage_TestRegistry
{
	protected $postResponseJobs = array();
	
	protected $configDir;
	public function __construct( $configDir ) {
		$this->configDir = $configDir;
	}
	
	protected $configCache = array();
	public function getConfig( $name ) {
		$parts = explode('/', $name);
		$file = array_shift($parts);
		if( isset($this->configCache[$file]) ) {
			$c = $this->configCache[$file];
		} else {
			$cf = "{$this->configDir}/{$file}.json";
			if( !file_exists($cf) ) return null;
			$c = EarthIT_JSON::decode(file_get_contents($cf), true);
			if( $c === null ) {
				throw new Exception("Failed to load config from '{$cf}'");
			}
			$this->configCache[$file] = $c;
		}
		foreach( $parts as $p ) {
			if( isset($c[$p]) ) {
				$c = $c[$p];
			} else {
				return null;
			}
		}
		return $c;
	}
	
	public function loadDbAdapter() {
		return Doctrine_DBAL_DriverManager::getConnection( $this->getConfig('dbc') );
	}
	
	public function loadDbObjectNamer() {
		return new EarthIT_DBC_OverridableNamer(new EarthIT_DBC_PostgresNamer());
	}
		
	public function loadSchema($name='') {
		return require EarthIT_Storage_ROOT_DIR.'/test/schema.php';
	}

	public function loadSqlRunner() {
		return new EarthIT_DBC_DoctrineSQLRunner($this->dbAdapter);
	}
	
	protected function loadPostgresStorage() {
		return new EarthIT_Storage_PostgresStorage(
			$this->schema, $this->sqlRunner, $this->dbObjectNamer );
	}
	
	protected function loadRester() {
		return new EarthIT_CMIPREST_RESTer( array(
			'storage' => $this->storage,
			'schema' => $this->schema,
			'keyByIds' => true,
			'authorizer' => $this->restActionAuthorizer
		));
	}
	
	public function loadMailer() {
		$transportConfig = $this->getConfig('email-transport');
		
		$encryptionMethod = coalesce($transportConfig['encryption']); // 'SSL' and 'TLS' are supported

		$transport = Swift_SmtpTransport::newInstance($transportConfig['host'], coalesce($transportConfig['port'],25));
		$transport->setUsername($transportConfig['username']);
		$transport->setPassword($transportConfig['password']);
		if( $encryptionMethod) $transport->setEncryption(strtolower($encryptionMethod));
		
		if( $recipientOverride = coalesce($transportConfig['recipient-override']) ) {
			$transport = new EarthIT_Storage_Email_RecipientOverrideTransport($transport, $recipientOverride);
		}
		
		return Swift_Mailer::newInstance($transport);
	}
	
	protected function readLstFile( $f ) {
		$data = file_get_contents($f);
		$rez = array();
		foreach( explode("\n",$data) as $l ) {
			$l = trim($l);
			if( $l == '' ) continue;
			if( $l[0] == '#' ) continue;
			$rez[] = $l;
		}
		return $rez;
	}
	
	protected function getBlobRepositoryDirs() {
		$repoListFile = "{$this->configDir}/local-ccouch-repos.lst";
		if( file_exists($repoListFile) ) {
			$repos = $this->readLstFile($repoListFile);
		} else {
			$repos = array();
		}
		array_unshift($repos, EarthIT_Storage_ROOT_DIR.'/datastore');
		return $repos;
	}
	
	protected function loadN2rServer() {
		$repos = array();
		foreach( $this->getBlobRepositoryDirs() as $rd ) {
			$repos[] = new TOGoS_PHPN2R_FSSHA1Repository($rd);
		}
		return new TOGoS_PHPN2R_Server($repos);
	}

	protected function loadPrimaryBlobRepository() {
		foreach( $this->getBlobRepositoryDirs() as $rd ) {
			return new TOGoS_PHPN2R_FSSHA1Repository($rd);
		}
		throw new Exception("No local repositories configured.");
	}
	
	protected function getViewTemplateDirectory() {
		return EarthIT_Storage_ROOT_DIR.'/views';
	}
	
	/**
	 * Components that have been explicitly configured.  Will not be
	 * wiped out by clean().
	 */
	protected $components = array();

	/**
	 * Components loaded lazily which will presumably be loaded the
	 * same way again if the the cache is cleared.  Will be emptied by
	 * clean().
	 */
	protected $cachedComponents = array();
	
	public function __isset($attrName) {
		try {
			return $this->$attrName !== null;
		} catch( Exception $e ) {
			throw $e;
			return false;
		}
	}
	
	/*
	 * List names of any component classes that where the casing of their ClassName
	 * differes from that of their attributeName by more than the first letter.
	 * e.g. classes whose names contain acronyms.
	 *
	 * If you've defined a loadXyz function, then this is unnecessary.
	 */
	protected static $funnilyCasedComponentNames = array('ABC decoder', 'REST action authorizer');
	
	public function __get($attrName) {
		// If something's been explicitly overridden, return that.
		if( isset($this->components[$attrName]) ) {
			return $this->components[$attrName];
		}
		
		// If there's a getter, call it and immediately return.
		$ucfAttrName = ucfirst($attrName);
		$getterMethodName = "get{$ucfAttrName}";
		if( method_exists($this, $getterMethodName) ) { 
			return $this->$getterMethodName();
		}

		// Check the cache.
		if( isset($this->cachedComponents[$attrName]) ) {
			return $this->cachedComponents[$attrName];
		}

		// If there's a loadX method, use it and cache the result.
		$creatorMethodName = "load{$ucfAttrName}";
		if( method_exists($this, $creatorMethodName) ) { 
			return $this->cachedComponents[$attrName] = $this->$creatorMethodName();
		}
		
		foreach( self::$funnilyCasedComponentNames as $n) {
			$n = trim($n);
			if( EarthIT_Schema_WordUtil::toCamelCase($n) == $attrName ) {
				// Ooh, this is what they want!
				$ucfAttrName = EarthIT_Schema_WordUtil::toPascalCase($n);
				break;
			}
		}
		
		// If there's a class with a matching name, instantiate it and cache the instance.
		$className = "EarthIT_Storage_{$ucfAttrName}";
		if( class_exists($className,true) ) {
			return $this->cachedComponents[$attrName] = new $className($this);
		}
		
		throw new Exception("Undefined property: ".get_class($this)."#$attrName");
	}
	
	/**
	 * Use to explicitly override a component.
	 * 
	 * Don't use this directly.  Use with(...) instead to make a copy
	 * of the registry with the specified things replaced.a
	 */
	public function __set($attrName, $value) {
		$this->components[$attrName] = $value;
	}
	
	/**
	 * Don't use this directly, either.
	 * Use cleanClone() to get a copy of the registry with the cache cleared.
	 */
	protected function clean() {
		$this->cachedComponents = array();
	}

	/**
	 * Returns a copy of this Registry with the component cache cleared.
	 *
	 * This ensures that if any settings are changed on the clone that
	 * would affect how components are reloaded, their new values get
	 * used to load those components when they are requested.
	 */
	public function cleanClone() {
		$c = clone $this;
		$c->clean();
		return $c;
	}
	
	public function with(array $stuff) {
		$alt = $this->cleanClone();
		foreach( $stuff as $k=>$v ) $alt->$k = $v;
		return $alt;
	}
	
	public function withSchema(EarthIT_Schema $schema) {
		return $this->with(array('schema'=>$schema));
	}
	public function withNamedSchema($name) {
		return $this->withSchema($this->loadSchema($name));
	}
}
