# Behat gif report

For behat 3. 

- Renders gif images of every step in test (except for 'Given' steps)
- Optional: Compile steps into a gif animation
- Optional: Send snapshot of failed step to slack
- Optional: Send movie of complete test run to slack

### Installation

Install using composer:

`composer require farald/gifReport`

By default, it will export images from all steps run.
Use this package on your local dvelopment to create a simple visual compilation of your work.

To enable, configure your list of contexts in `behat.yml`:

```yaml
  suites:
    default:
      contexts:
        - Farald\GifReport\GifReportContext:
            params:
              imageDir: "/full/path/to/empty/dir"
              gifAnimDir: "/full/path/to/another/empty/dir"
              projectTitle: "A title"
              slackApiToken: "foo"
              slackChannel: "#test-uploaded-results"
              slackPostAs: "Reports"
              ```

imageDir and gifAnimDir should be empty directories. It will be emptied at start of every behat run.
projectTitle is used primarily as title for the images

Once the script has run, your imageDir folder should contain images for every step that was run, except for
`given` steps (`when`, `then' & `and` will all provide a screenshot).

After a complete run, a gif compiled from the step images will reside in your anim directory.
