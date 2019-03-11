<?php
namespace JsonCollectionParser;

class Parser
{
    /**
     * @var array
     */
    protected $options = [
        'line_ending' => "\n",
        'emit_whitespace' => false,
        'buffer_size' => 8192,
    ];

    /**
     * @var \JsonStreamingParser\Parser
     */
    protected $parser;

    /**
     * @var bool
     */
    protected $gzipSupported;
  
    /**
     * @var \JsonCollectionParser\Listener
     */
    protected $listener;

    /**
     * @var resource
     */
    protected $stream;
  
    protected $fileStat;
  
    protected $flagContinue = false;

    public function __construct()
    {
        $this->gzipSupported = extension_loaded('zlib');
        $this->listener = null;
    }

    /**
     * @param string|resource $input File path or resource
     * @param callback|callable|Listener $itemCallbackOrListener Callback
     * @param bool $assoc Parse as associative arrays
     *
     * @throws \Exception
     */
    public function parse($input, $itemCallbackOrListener, $assoc = true)
    {
        $this->stream = $this->openStream($input);
        $this->fileStat = stat($input);
        if (null !== ($offset = $this->getOption('offset'))) {
          if (-1 == fseek($this->stream, $offset)) {
            throw new \Exception('Can\'t set correct offset of ' . $offset . ' bytes for the file');
          }
          
          $this->flagContinue = $offset > 0;
        }

        try {
            if ($itemCallbackOrListener instanceof Listener) {
              $this->setListener($itemCallbackOrListener);
            }
          
            if ($this->listener === null) {
              $this->checkCallback($itemCallbackOrListener);
              $this->setListener(new Listener($itemCallbackOrListener, $assoc));
            }
            
            $this->listener->setParser($this);
            $this->parser = new \JsonStreamingParser\Parser(
                $this->stream,
                $this->listener,
                $this->getOption('line_ending'),
                $this->getOption('emit_whitespace'),
                $this->getOption('buffer_size')
            );
          
            if ($this->eof()) {
              // Set the true EOF
              fseek($this->stream, 0, SEEK_END);
            }
            
            $this->parser->parse();
        } catch (\Exception $e) {
            $this->closeStream();
            throw $e;
        }
    }
    
    public function closeStream()
    {
      $this->gzipSupported ? gzclose($this->stream) : fclose($this->stream);
    }
    
    public function __destruct()
    {
      $this->closeStream();
      unset($this->parser);
    }
  
  /**
     * @param string|resource $input File path or resource
     * @param callback|callable $itemCallback Callback
     *
     * @throws \Exception
     */
    public function parseAsObjects($input, $itemCallback)
    {
        $this->parse($input, $itemCallback, false);
    }

    /**
     *
     */
    public function stop()
    {
        $this->parser->stop();
    }

    /**
     * @param callback|callable $callback
     *
     * @throws \Exception
     */
    protected function checkCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback should be callable");
        }
    }

    /**
     * @param string|resource $input File path or resource
     *
     * @return resource
     * @throws \Exception
     */
    protected function openStream($input)
    {
        if (is_resource($input)) {
            return $input;
        }

        if (!is_file($input)) {
            throw new \Exception('File does not exist: ' . $input);
        }

        $stream = $this->gzipSupported ? @gzopen($input, 'r') : @fopen($input, 'r');
        if (false === $stream) {
            throw new \Exception('Unable to open file for read: ' . $input);
        }

        return $stream;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        } else {
            return null;
        }
    }
    
    public function setListener(Listener $listener) {
      $this->listener = $listener;
    }
    
    public function eof() {
      $eof = feof($this->stream);
      if (!$eof) {
        $pos = $this->getPosition();
        stream_get_line($this->stream, $this->getOption('buffer_size'), $this->getOption('line_ending'));
        $endpos = $this->getPosition();
        $eof = $endpos === $pos;
        
        // rewind back
        fseek($this->stream, $pos);
      }
      
      return $eof;
    }
    
    public function getPosition() {
      return $this->parser->getPosition();
    }
  
    public function getPositionObject() {
      return $this->parser->getPosition(\JsonStreamingParser\Parser::POSITION_OBJECT);
    }
    
    public function getPositionArray() {
      return $this->parser->getPosition(\JsonStreamingParser\Parser::POSITION_ARRAY);
    }
    
    public function getProgressPercent() {
      // 100 is max
      return (($this->getPosition('object') * 100) / $this->fileStat['size']);
    }
    
    public function isFlagContinue($switch = TRUE) {
      if ($this->flagContinue && $switch) {
        $this->flagContinue = false;
        return true;
      }
      
      return $this->flagContinue;
    }
    
    public function getBytesRead() {
      return $this->parser->getBytesRead();
    }
}
