<?php

namespace SortBundle\Command;

use SortBundle\Services\Stream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SortFileCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('file:sort')
            ->setDescription('Hello PhpStorm')
            ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'sorting metohod [standard,database]');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createTableHelper($output);
        $sInputFileName = $this->askInputFileName($output, $input);
        $sOutputFileName = $this->askOutputFileName($output, $input);
        $SortService = $this->getSortingService($input);
        $SotDirection = $this->askSortingDirection($output, $input);
        $progress = new ProgressBar($output);
        $progress->start();
        $progress->setFormat('[%bar%] %elapsed:6s%');
        $Callback = function () use ($progress) {
            $progress->advance();
        };
        $this->saveSortedData($SortService->sort($this->getFileDirectory() . $sInputFileName, $Callback, $SotDirection), $sOutputFileName, $Callback);
        $progress->finish();
        $output->writeln('');
    }

    /**
     * Zapisuje otrzymane dane do pliku
     * @param array $aData
     * @param string $sFileName
     * @param \Closure|null $Callback
     * @return boolean
     */
    protected function saveSortedData(array $aData, string $sFileName, \Closure $Callback = null)
    {
        $sDirectory = $this->getFileDirectory() . "output";
        $this->createDirIfNotExist($sDirectory);
        $rFile = fopen($sDirectory ."/". $sFileName . ".txt", "w");
        $index = 0;
        $aRowData = [];
        foreach ($aData as $fValue) {
            $aRowData[] = $fValue;
            $index++;
            if ($index % 10000 == 0) {
                fwrite($rFile, join("|", $aRowData));
                $aRowData = [];
                $index = 0;
                $Callback();
            }
        }
        fwrite($rFile, join("|", $aRowData));
        return fclose($rFile);
    }

    /**
     * Tworzy katalog jeżeli nie istnieje
     * @param string $sDirectory
     */
    protected function createDirIfNotExist(string $sDirectory)
    {
        if (!is_dir($sDirectory)) {
            mkdir($sDirectory, 0775);
        }
    }

    /**
     * Pyta użytkownia o kierunek sortowania i zwraca wyniki
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return array:string ['ASC','DESC']
     */
    protected function askSortingDirection(OutputInterface $output, InputInterface $input)
    {
        $Helper = $this->getHelper('question');
        $question = new Question('<fg=green>Please enter the sorting direction[ASC,DESC]:</>', 'ASC');
        $question->setAutocompleterValues(['ASC', 'DESC']);
        $question->setValidator(function ($answer) {
            if (empty($answer) || !in_array(strtoupper($answer), ['ASC', 'DESC'])) {
                throw new \RuntimeException(
                    'The direction "' . $answer . '" is unknown'
                );
            }
            return $answer;
        });
        return $Helper->ask($input, $output, $question);
    }

    /**
     * Zależnie od wybranego algorytmu zwraca odpowidni service sortujący
     * @param InputInterface $input
     * @return object|\SortBundle\Services\Standard
     */
    protected function getSortingService(InputInterface $input)
    {
        $sMetohodName = strtolower($input->getOption('method'));
        if (in_array($sMetohodName, ['database', 'standard'])) {
            return $this->getContainer()->get('sort.' . $sMetohodName);
        }
        return $this->getContainer()->get('sort.standard');
    }

    /**
     * Pyta użytkownia o nazwe pliku wejsciowego
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return string
     */
    protected function askInputFileName(OutputInterface $output, InputInterface $input):string
    {
        $Helper = $this->getHelper('question');
        $question = new Question('<fg=green>Please enter the name of input file:</>');
        $aFileList = [];
        foreach ($this->listFileInDirecory($this->getFileDirectory()) as $aFileInfo) {
            $aFileList[] = $aFileInfo['name'];
        }
        $question->setAutocompleterValues($aFileList);
        $question->setValidator(function ($answer) {
            if (preg_match('/[^A-Za-z0-9 _ .-]/', $answer) || empty($answer)) {
                throw new \RuntimeException(
                    'The name "' . $answer . '" is incorrect. The file name can only contain "a-Z", "0-9" and "_ . -", not null'
                );
            }
            return $answer;
        });
        return $Helper->ask($input, $output, $question);
    }

    /**
     * Pyta użytkownia o nazwe pliku wyjściowego
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return string
     */
    protected function askOutputFileName(OutputInterface $output, InputInterface $input):string
    {
        $Helper = $this->getHelper('question');
        $sName = md5(microtime(true));
        $question = new Question('<fg=green>Please enter the name of output file[' . $sName . ']:</>', $sName);
        $question->setValidator(function ($answer) {
            if (preg_match('/[^A-Za-z0-9 _ .-]/', $answer) || empty($answer)) {
                throw new \RuntimeException(
                    'The name "' . $answer . '" is incorrect. The file name can only contain "a-Z", "0-9" and "_ . -", not null'
                );
            }
            return $answer;
        });
        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $Helper->ask($input, $output, $question));
    }

    /**
     * Renderuje tabelkę z podpowiedziami jakie pliki znajdują się w katalogu do sortowania
     * @param OutputInterface $output
     */
    protected function createTableHelper(OutputInterface $output)
    {
        $table = new Table($output);
        $table
            ->setHeaders(array('File name', 'Size'))
            ->setRows($this->listFileInDirecory($this->getFileDirectory()));
        $table->render();
    }

    /**
     * Listuje wszystkie pliki w katalogu z rozszeżeniem txt
     * @param string $sDirectory
     * @return array
     */
    protected function listFileInDirecory(string $sDirectory):array
    {
        $aFileInfo = [];
        foreach (glob($sDirectory . "*.txt") as $filename) {
            $aFileInfo[] = ['name' => basename($filename), 'size' => $this->Size($filename)];
        }
        return $aFileInfo;
    }

    /**
     * Zwraca ścieżkę gdzie będą przechowywane pliki
     * @return string
     */
    protected function getFileDirectory():string
    {
        return $this->getContainer()->get('kernel')->getRootDir() . "/../var/";
    }

    /**
     * Zwraca rozmiar pliku w ['B', 'KB', 'MB', 'GB']
     * @param $path
     * @return string
     */
    protected function Size($path):string
    {
        $bytes = sprintf('%u', filesize($path));

        if ($bytes > 0) {
            $unit = intval(log($bytes, 1024));
            $units = ['B', 'KB', 'MB', 'GB'];

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }
}
