<?php

namespace Farald\GifReport;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use CURLFile;
use Gregwar\Image\Image;
use Composer\Autoload\ClassLoader;


/**
 * Class FailedStepToSlack
 *
 * @package Farald\GifReport
 */
class FailedStepToSlack extends RawMinkContext
{
  private $screenShotPath;
  private $slackToken;
  private $slackChannel;
  private $postAs;



  /**
   * FailedStepToSlack constructor.
   *
   * @param mixed $screenShotPath
   *   Screenshot path.
   * @param mixed $slackToken
   *   Slack Token.
   * @param mixed $slackChannel
   *   The slack channel to post to.
   * @param mixed $postAs
   *   The screenshot path.
   */
  public function __construct($screenShotPath, $slackToken, $slackChannel, $postAs) {
    $this->screenShotPath = $screenShotPath;
    $this->slackToken = $slackToken;
    $this->slackChannel = $slackChannel;
    $this->postAs = $postAs;
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
  public function generateScreenshotAfterFailedStep(AfterStepScope $scope) {
    if (99 === $scope->getTestResult()->getResultCode()) {
      $driver = $this->getSession()->getDriver();
      if (!$driver instanceof Selenium2Driver) {
        return;
      }
      if (!is_dir($this->screenShotPath)) {
        mkdir($this->screenShotPath, 0777, TRUE);
      }
      $filename = sprintf(
        '%s_%s_%s.%s',
        $this->getMinkParameter('browser_name'),
        date('Ymd') . '-' . date('His'),
        uniqid('', TRUE),
        'png'
      );
      $result_color = 0x0b490d;
      $result_text = 'PASS';
      if (99 === $scope->getTestResult()->getResultCode()) {
        $result_color = 0x7c0101;
        $result_text = 'FAIL';
      }
      $this->saveScreenshot($filename, $this->screenShotPath);
      // Load the file to write text.
      $text = $scope->getStep()->getKeyword() . " " . $scope->getStep()
          ->getText();
      // Get current fonts dir.
      $reflection = new \ReflectionClass(ClassLoader::class);
      $vendorDir = dirname(dirname($reflection->getFileName()));
      $packageDir = $vendorDir . '/farald/gifreport';
      $fontsDir = $packageDir . '/fonts/';
      Image::open($this->screenShotPath . '/' . $filename)
        ->crop(0, 0, 1440, 1000)
        ->rectangle(0, 900, 1440, 1000, 0xdddddd, TRUE)
        ->rectangle(1300, 900, 1440, 1000, $result_color, TRUE)
        ->write($fontsDir . 'OpenSans-Regular.ttf', $result_text, 1310, 970, 40, 0, 0xffffff)
        ->line(0, 900, 1440, 900, 0xbbbbbb)
        ->write($fontsDir . 'OpenSans-Regular.ttf', 'Feature: ' . $scope->getFeature()
            ->getTitle(), 30, 940, 16, 0, 0x444444)
        ->write($fontsDir . 'OpenSans-Regular.ttf', $text, 30, 970, 12, 0, $result_color)
        ->save($this->screenShotPath . '/' . $filename);
      // Post to slack.
      exec('git rev-parse --abbrev-ref HEAD', $gitOutput2);
      $branch = $gitOutput2[0];
      $postitems = [
        'token' => $this->slackToken,
        'file' => $file = new CurlFile($this->screenShotPath . '/' . $filename, 'image/jpeg'),
        'text' => $branch,
        'title' => $branch,
        'filename' => "failed-step.jpg",
        'filetype' => 'jpg',
        'channels' => $this->slackChannel,
      ];
      $header = [];
      $header[] = 'Content-Type: multipart/form-data';
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
      curl_setopt($curl, CURLOPT_URL, "https://slack.com/api/files.upload");
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postitems);
      curl_exec($curl);
    }
  }


}