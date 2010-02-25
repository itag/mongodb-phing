<?php
/*
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
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/Task.php';

/**
 *  
 * @abstract 
 */
abstract class MongoDbToolsTask extends Task
{
    protected $_host;
    protected $_db;
    protected $_collection;
    protected $_username;
    protected $_password;
    protected $_dbpath;
    /**
     * @var array
     */
    protected $_supportedTools;
    
    /**
     * Initializes the common properties.
     */
    public function init()
    {
        $this->_host = '127.0.0.1';
        $this->_supportedTools = array(
            'mongoimport', 
            'mongoexport', 
            'mongorestore',
            'mongodump', 
            'mongofiles'
        );
    }
    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->_host = $host;
    }
    /**
     * @param string $db
     */
    public function setDb($db)
    {
        $this->_db = $db;
    }
    /**
     * @param string $collection
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;
    }
    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->_username = $username;
    }
    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->_password = $password;
    }
    /**
     * @param string $dbpath
     */
    public function setDbpath($dbpath)
    {
        $this->_dbpath = $dbpath;
    }
    /**
     * Validates the common task properties.
     *
     * @throws BuildException
     */
    protected function _validateCommonTaskProperties()
    {
        if (null === $this->_collection || '' === $this->_collection) {
            throw new BuildException('Mandatory attribute collection not set.');
        }
        if (null === $this->_db || '' === $this->_db) {
            throw new BuildException('Mandatory attribute db not set.');
        }
        if (null !== $this->_dbpath) {
            if (!is_readable($this->_dbpath)) {
                $exceptionMessage = "Specified mongod data path '%s' does not " 
                    .  "exist or is not readable.";
                $exceptionMessage = sprintf($exceptionMessage, $this->_dbpath);
                throw new BuildException($exceptionMessage);
            }
        }
        if (null !== $this->_username && null === $this->_password) {
            $exceptionMessage = "No password set while username specified.";
            throw new BuildException($exceptionMessage);        
        }
        if (null !== $this->_password && null === $this->_username) {
            $exceptionMessage = "No username set while password specified.";
            throw new BuildException($exceptionMessage);
        }
    }
    /**
     * Checks if the mongo tool is available on the system.
     *
     * @param string   $tool The name of the mongo tool
     * @return boolean
     */
    protected function _isToolAvailable($tool)
    {
        if (!in_array($tool, $this->_supportedTools)) {
            return false;
        }
        $command = sprintf('%s --help 2>/dev/null', $tool);
        exec($command, $out, $code);
        return $code === 0;
    }
    /**
     * Validation method to be implemented by specific/child tasks.
     *
     * @throws BuildException
     */
    abstract protected function _validateTaskProperties();
}