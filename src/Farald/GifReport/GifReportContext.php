<?php

namespace Farald\GifReport;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\MinkExtension\Context\RawMinkContext;
use GifCreator\AnimGif;
use Gregwar\Image\Image;

/**
 * Class GifReportContext.
 *
 * @package \RegistreringssystemABC\Tests\Context
 */
class GifReportContext extends RawMinkContext {

  protected $linesTotal;
  protected $featureName;
  protected $featureLines;
  protected $stepText;
  protected $stepKeyword;
  protected $stepLine;
  protected $stepPercentage;
  protected $firstInFile;
  protected $setupStepPercentage;

  /**
   * File paths, and other configuration.
   */
  protected $filePath = __DIR__ . '/../../output/';
  protected $doClearDownloadFolder = TRUE;
  protected $doGenerateGifAnim = TRUE;
  protected $docTitle = "Test title";

  /**
   * Check if selenium driver.
   */
  public function isSeleniumDriver() {
    $driver = $this->getSession()->getDriver();
    return (!$driver instanceof Selenium2Driver) ? FALSE : TRUE;
  }

  /**
   * Set a tag for a new feature.
   *
   * We can access that when we are ready
   * to display our front "page".
   *
   * @BeforeFeature
   */
  public static function setNewFeature() {
    global $_startingNewFeature;
    $_startingNewFeature = TRUE;
  }

  /**
   * Gather some data that we will reuse at a later time.
   *
   * This is data that we need for all report parts.
   *
   * We regenerate this between steps, to get titles & percentages
   * etc needed when generating pages.
   *
   * @BeforeStep
   */
  public function stepData(BeforeStepScope $scope) {
    $this->firstInFile = (!$this->stepLine) ? TRUE : $this->firstInFile;
    $this->stepKeyword = $scope->getStep()->getKeyword();
    $this->stepText = $scope->getStep()->getText();
    $this->stepLine = $scope->getStep()->getLine();
    $scenarios = $scope->getFeature()->getScenarios();
    $last_scenario = end($scenarios)->getSteps();
    $this->linesTotal = end($last_scenario)->getLine();
    $this->featureName = $scope->getFeature()->getTitle();
    $this->stepPercentage = (round(((100 / $this->linesTotal) * $this->stepLine), 0));
  }

  /**
   * Handles the generation of a front page.
   *
   * @todo This should be independent of seleniumDriver.
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $scope
   *   The step scope.
   *
   * @throws \Exception
   */
  public function generateFrontPageScreenshot(AfterStepScope $scope) {
    global $_screenShotCount;
    $description = $scope->getFeature()->getDescription();
    if (function_exists('xdebug_break')) {
      return;
    }
    $fileName = str_pad($_screenShotCount, 3, '0', STR_PAD_LEFT) . '-' . time() . '-' . uniqid() . '.png';
    $filePath = __DIR__ . '/../../output/';
    if (!is_writable($filePath)) {
      mkdir($filePath);
    }
    $this->saveScreenshot($fileName, $filePath);
    Image::open($filePath . $fileName)
      ->crop(0, 0, 1440, 1000)
      ->rectangle(0, 0, 1440, 1000, 0xffffff, TRUE)
      ->write(__DIR__ . '/../../fonts/OpenSans-Bold.ttf', strtoupper($this->docTitle), 30, 80, 25, 0, 0x333333)
      ->write(__DIR__ . '/../../fonts/OpenSans-Bold.ttf', strtoupper($this->featureName), 30, 150, 18, 0, 0x333333)
      ->write(__DIR__ . '/../../fonts/OpenSans-Regular.ttf', $description, 30, 200, 16, 0, 0x333333)
      ->rectangle(0, 900, 1440, 1000, 0xdddddd, TRUE)
      ->line(0, 900, 1440, 900, 0xbbbbbb)
      ->rectangle(1300, 900, 1440, 1000, 0xbbbbbb, TRUE)
      ->rectangle(0, 995, ((1300 / 100) * ($this->setupStepPercentage)) - 5, 1000, 0x666666, TRUE)
      ->write(__DIR__ . '/../../fonts/OpenSans-Regular.ttf', 'Feature: ' . $this->featureName, 30, 940, 16, 0, 0x444444)
      ->write(__DIR__ . '/../../fonts/OpenSans-Regular.ttf', "0-$this->stepPercentage % INIT SETUP", 30, 970, 12, 0, 0x444444)
      ->save($filePath . $fileName);
    $_screenShotCount++;
  }

  /**
   * Generate a screenshot after failed step, and add some information.
   *
   * This first saves a screenshot from the selenium driver, then reopens it
   * for modification. It will add text and other information available to us
   * from the Selenium driver.
   *
   * @AfterStep
   */
  public function generateScreenshotAfterStep(AfterStepScope $scope) {
    global $_screenShotCount;
    if ($scope->getStep()->getKeyword() == "Given") {
      return;
    }
    if (function_exists('xdebug_break')) {
      return;
    }
    $driver = $this->getSession()->getDriver();
    if (!$driver instanceof Selenium2Driver) {
      return;
    }
    // Generate a front screenshot.
    global $_screenshotFolderCleared;
    if (!$_screenshotFolderCleared) {
      $this->clearScreenshotFolder();
      $_screenshotFolderCleared = TRUE;
    }
    global $_startingNewFeature;

    if ($this->firstInFile && $_startingNewFeature) {
      $this->setupStepPercentage = $this->stepPercentage;
      $this->generateFrontPageScreenshot($scope);
      $_startingNewFeature = FALSE;
    }
    $_screenShotCount++;
    $fileName = str_pad($_screenShotCount, 3, '0', STR_PAD_LEFT) . '-' . time() . '-' . uniqid() . '.png';
    $filePath = $this->filePath;
    if (!is_writable($filePath)) {
      mkdir($filePath);
    }
    $result_color = 0x0b490d;
    $result_text = 'PASS';
    if (99 === $scope->getTestResult()->getResultCode()) {
      $result_color = 0x7c0101;
      $result_text = 'FAIL';
    }
    $this->saveScreenshot($fileName, $filePath);
    // Load the file to write text.
    $text =
      $this->stepPercentage . ' % - ' .
      $this->stepKeyword . " " . $this->stepText;
    Image::open($filePath . $fileName)
      ->crop(0, 0, 1440, 1000)
      ->rectangle(0, 900, 1440, 1000, 0xdddddd, TRUE)
      ->rectangle(1300, 900, 1440, 1000, $result_color, TRUE)
      ->rectangle(0, 995, ((1300 / 100) * $this->stepPercentage), 1000, $result_color, TRUE)
      ->rectangle(0, 995, ((1300 / 100) * $this->setupStepPercentage) - 5, 1000, 0x666666, TRUE)
      ->write(__DIR__ . '/../../fonts/OpenSans-Regular.ttf', $result_text, 1310, 970, 40, 0, 0xffffff)
      ->line(0, 900, 1440, 900, 0xbbbbbb)
      ->write(__DIR__ . '/../../fonts/OpenSans-Regular.ttf', 'Feature: ' . $this->featureName, 30, 940, 16, 0, 0x444444)
      ->write(__DIR__ . '/../../fonts/OpenSans-Regular.ttf', $text, 30, 970, 12, 0, $result_color)
      ->save($filePath . $fileName);
  }

  /**
   * Clear screenshot folder.
   */
  public function clearScreenshotFolder() {
    if ($this->doClearDownloadFolder) {
      $filePath = $this->filePath;
      $files = glob($filePath . '*');
      foreach ($files as $file) {
        if (is_file($file)) {
          unlink($file);
        }
      }
    }
  }

  /**
   * When Xdebug is active, do not create animation.
   *
   * This will slow down the test execution a lot.
   *
   * @AfterSuite
   */
  public static function createAnimFromScreenshots() {
    if (function_exists('xdebug_break')) {
      return;
    }
    $filePath = __DIR__ . '/../../output/';
    $anim_path = __DIR__ . '/../../anim/';
    $frames = $filePath;
    $durations = [210];
    $anim = new AnimGif();
    $anim->create($frames, $durations);
    $anim->save($anim_path . "animated.gif");
  }

}
