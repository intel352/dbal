<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Import_Builder
 * Import builder is responsible of building Doctrine ActiveRecord classes 
 * based on a database schema.
 *
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 */
class Doctrine_Import_Builder {
    
    private $path = '';
    
    private $suffix = '.php';

    private static $tpl;

    public function __construct() {
        if( ! isset(self::$tpl))
            self::$tpl = file_get_contents(Doctrine::getPath()
                       . DIRECTORY_SEPARATOR . 'Doctrine'
                       . DIRECTORY_SEPARATOR . 'Import'
                       . DIRECTORY_SEPARATOR . 'Builder'
                       . DIRECTORY_SEPARATOR . 'Record.tpl');
    }

    /**
     *
     * @param string path
     * @return 
     * @access public
     */
    public function setTargetPath($path) {
        if( ! file_exists($path)) {
            mkdir($path, 0777);
        }

        $this->path = $path;
    }
    public function getTargetPath() {
        return $this->path;
    }
    /**
     *
     * @param string path
     * @return
     * @access public
     */
    public function setFileSuffix($suffix) {
        $this->suffix = $suffix;
    }
    public function getFileSuffix() {
        return $this->suffix;
    }


    public function buildRecord(Doctrine_Schema_Table $table) {
        if (empty($this->path)) 
            throw new Doctrine_Import_Builder_Exception('No build target directory set.');

        if (is_writable($this->path) === false) 
            throw new Doctrine_Import_Builder_Exception('Build target directory ' . $this->path . ' is not writable.');

        $created   = date('l dS \of F Y h:i:s A');
        $className = Doctrine::classify($table->get('name'));
        $fileName  = $this->path . DIRECTORY_SEPARATOR . $className . $this->suffix;
        $columns   = array();

        $i = 0;

        foreach($table as $name => $column) {

            $columns[$i] = '        $this->hasColumn(\'' . $column['name'] . '\', \'' . $column['type'] . '\'';
            if($column['length'])
                $columns[$i] .= ', ' . $column['length'];
            else
                $columns[$i] .= ', null';
           
            $a = array();
            
            if($column['default']) {
                $a[] = '\'default\' => ' . var_export($column['default'], true);
            }
            if($column['notnull']) {
                $a[] = '\'notnull\' => true';
            }
            if($column['primary']) {
                $a[] = '\'primary\' => true';
            }
            if($column['autoinc']) {
                $a[] = '\'autoincrement\' => true';
            }
            if($column['unique']) {
                $a[] = '\'unique\' => true';
            }

            if( ! empty($a))
                $columns[$i] .= ', ' . 'array(' . implode(',
', $a) . ')';

            $columns[$i] .= ');';
            
            if($i < (count($table) - 1))
                $columns[$i] .= '
';
            $i++;
        }

        $content   = sprintf(self::$tpl, $created, $className, implode('', $columns));

        $bytes     = file_put_contents($fileName, $content);


        if($bytes === false)
            throw new Doctrine_Import_Builder_Exception("Couldn't write file " . $fileName);
    }
    /**
     *
     * @param Doctrine_Schema_Object $schema
     * @return
     * @access public
     * @throws Doctrine_Import_Exception
     */
    public function build(Doctrine_Schema_Object $schema) {
	foreach($schema->getDatabases() as $database){
		foreach($database->getTables() as $table){
			$this->buildRecord($table);
		}
	}
    }

}
