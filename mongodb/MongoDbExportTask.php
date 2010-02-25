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

require_once 'MongoDbToolsTask.php';

class MongoDbExportTask extends MongoDbToolsTask
{
    private $_type;
    private $_fields;
    private $_query;
    private $_outputFile;
    /**
     * @var array
     */
    private $_supportedTypes;
    private $_tool;
    
    /**
     * Initializes the common and specific properties.
     */
    public function init()
    {
        parent::init();
        $this->_supportedTypes = array('csv', 'json');
        $this->_type = 'json';
        $this->_tool = 'mongoexport';
    }
    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->_type = strtolower($type);
    }
    /**
     * @param string $fields A comma seperated list of field names
     */
    public function setFields($fields)
    {
        $this->_fields = $fields;
    }
    /**
     * @param string $query A query filter as a JSON string
     */
    public function setQuery($query) 
    {
        $this->_query = $query;
    }
    /**
     * @param string $outputFile The output file to export to 
     */
    public function setOutputFile($outputFile) 
    {
        $this->_outputFile = $outputFile;
    }
    /**
     * @throws BuildException
     */
    protected function _validateTaskProperties()
    {  
        if (!in_array($this->_type, $this->_supportedTypes)) {
            $exceptionMessage = "Specified export type '%s' is not supported."
                . " Supported types are %s.";
            $exceptionMessage = sprintf(
                $exceptionMessage, 
                $this->_type,
                implode(', ', $this->_supportedTypes)
            );    
            throw new BuildException($exceptionMessage);
        }
        if ('csv' === $this->_type && null === $this->_fields) {
            $exceptionMessage = "Export type 'csv' requires fields to be set "
                . "via the fields attribute.";
            throw new BuildException($exceptionMessage);
        }
    }
    /**
     *
     * @see MongoDbToolsTask::_isToolAvailable
     * @throws BuildException
     */
    public function main()
    {
        if (null === $this->_outputFile) {
            $this->_outputFile = $this->_collection . '.' . $this->_type;
        }
        if (false === $this->_isToolAvailable($this->_tool)) {
            $exceptionMessage = "Required mongo tool '%s' is not available.";
            $exceptionMessage = sprintf($exceptionMessage, $this->_tool);
            throw new BuildException($exceptionMessage);
        }
        $this->_validateCommonTaskProperties();
        $this->_validateTaskProperties();
        $exports = $this->_mongoexport();
        $logMessage = sprintf("%s from '%s.%s' into '%s'.",
            $exports,
            $this->_db,
            $this->_collection,
            $this->_outputFile
        );
        $this->log($logMessage);
    }
    /**
     * Runs the mongo export tool.
     *
     * @return string The export summary
     * @throws BuildException
     */
    private function _mongoexport()
    {
        $command = sprintf("%s -d %s -c %s -o %s ", 
            $this->_tool,
            $this->_db,
            $this->_collection,
            $this->_outputFile
        );
        if (null !== $this->_host) {
            $command.= sprintf("-h %s ", $this->_host);
        }
        if (null !== $this->_username && null !== $this->_password) {
            $command.= sprintf("-u %s -p %s ", 
                $this->_username,
                $this->_password
            );
        }
        if (null !== $this->_dbpath) {
            $command.= sprintf("--dbpath %s ", 
                $this->_dbpath
            );
        }
        if (null !== $this->_query) {
            $command.= sprintf("-q %s ", 
                $this->_query
            );
        }
        if (null !== $this->_fields) {
            $command.= sprintf("-f %s ", 
                $this->_fields
            );
        }
        if ('csv' === $this->_type) {
            $command.= "--csv ";
        }
        $command.= "2>/dev/null";
        exec($command, $out, $code);
        if (0 !== $code) {
            $exceptionMessage = "Export failed for the mongo tool '%s'.";
            $exceptionMessage = sprintf($exceptionMessage, $command);
            throw new BuildException($exceptionMessage);
        }
        return ucfirst($out[0]);
    }
}