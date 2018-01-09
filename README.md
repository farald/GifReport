# Behat gif report

Enables you to render gif images of every step in your test run, and then compiles it into a gif animation. This can again be rendered to an avi movie using ffmpeg.

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
              projectTitle: "En tittel"
```

The image dir should be an empty directory. It will be emptied at start of every behat run.

Once the script has run, look in your images folder. It should contain images for every step that was run, except for
`given` steps (`when` and `then' & `and` will all provide a screenshot).

After a complete run, a gif compiled from the step images will reside in your anim directory.