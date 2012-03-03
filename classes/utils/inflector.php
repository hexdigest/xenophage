<?php
AutoLoad::path(dirname(__FILE__). '/inflections.php');
 
class Inflector {

    /**
     *  Pluralize a word according to English rules
     *
     *  Convert a lower-case singular word to plural form.
     *  @param  string $word  Word to be pluralized
     *  @return string  Plural of $word
     */
    public static function pluralize($word) {
			if (! in_array($word, Inflections::$uncountables)) { 
				$original = $word;   
					foreach(Inflections::$plurals as $plural_rule) {
						$word = preg_replace($plural_rule['rule'], $plural_rule['replacement'], $word);
						if ($original != $word) break;
					}
			}

			return $word;
    }

    /**
     *  Singularize a word according to English rules 
     *
     *  @param  string $word  Word to be singularized
     *  @return string  Singular of $word
     */
    public static function singularize($word) {
			if(! in_array($word, Inflections::$uncountables)) { 
				$original = $word;   
				foreach (Inflections::$singulars as $singular_rule) {
					$word = preg_replace($singular_rule['rule'], $singular_rule['replacement'], $word);
					if ($original != $word) break;
				}
			}

			return $word;
    }

    /**
     *  Capitalize a word making it all lower case with first letter uppercase 
     *
     *  @param  string $word  Word to be capitalized
     *  @return string Capitalized $word
     */
    public static function capitalize($word) {
			return ucfirst(strtolower($word));     
    }

    /**
     *  Convert a phrase from the lower case and underscored form
     *  to the camel case form
     *
     *  @param string $lower_case_and_underscored_word  Phrase to
     *                                                  convert
     *  @return string  Camel case form of the phrase
     */
    public static function camelize($lower_case_and_underscored_word) {
			return str_replace(" ", "", ucwords(str_replace("_", " ", 
				$lower_case_and_underscored_word)));
    }

    /**
     *  Convert a phrase from the camel case form to the lower case
     *  and underscored form
     *
     *  @param string $camel_cased_word  Phrase to convert
     *  @return string Lower case and underscored form of the phrase
     */
    public static function underscore($camel_cased_word) {
			$camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/', '\1_\2',
				$camel_cased_word);

			return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2',
				$camel_cased_word));
    }

    /**
     *  Generate a more human version of a lower case underscored word
     *
     *  @param string $lower_case_and_underscored_word  A word or phrase in
     *                                           lower_case_underscore form
     *  @return string The input value with underscores replaced by
     *  blanks and the first letter of each word capitalized
     */
    public static function humanize($lower_case_and_underscored_word) {
			return ucwords(str_replace("_", " ", $lower_case_and_underscored_word));
    }
    
    /**
     *  Convert a word or phrase into a title format "Welcome To My Site"
     *
     *  @param string $word  A word or phrase
     *  @return string A string that has all words capitalized and splits on existing caps.
     */    
    public static function titleize($word) {
			return preg_replace('/\b([a-z])/', self::capitalize('$1'), 
				self::humanize(self::underscore($word)));
    }

    /**
     *  Convert a word's underscores into dashes
     *
     *  @param string $underscored_word  Word to convert
     *  @return string All underscores converted to dashes
     */    
    public static function dasherize($underscored_word) {
			return str_replace('_', '-', self::underscore($underscored_word));
    }

    /**
     *  Convert a class name to the corresponding table name
     *
     *  The class name is a singular word or phrase in CamelCase.
     *  By convention it corresponds to a table whose name is a plural
     *  word or phrase in lower case underscore form.
     *  @param string $class_name  Name of {@link ActiveRecord} sub-class
     *  @return string Pluralized lower_case_underscore form of name
     */
    public static function tableize($class_name) {
			return self::pluralize(self::underscore($class_name));
    }

    /**
     *  Convert a table name to the corresponding class name
     *
     *  @param string $table_name Name of table in the database
     *  @return string Singular CamelCase form of $table_name
     */
    public static function classify($table_name) {
			return self::camelize(self::singularize($table_name));
    }

    /**
     *  Get foreign key column corresponding to a table name
     *
     *  @param string $table_name Name of table referenced by foreign
     *    key
     *  @return string Column name of the foreign key column
     */
    public static function foreign_key($class_name) {
			return self::underscore($class_name) . "_id";
    }
}
?>
