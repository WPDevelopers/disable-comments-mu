# Disable Comments for WordPress (Must-Use version)

This is the [must-use](http://codex.wordpress.org/Must_Use_Plugins) version of the [Disable Comments](http://wordpress.org/extend/plugins/disable-comments/) WordPress plugin.

Copy the contents of this directory into your `mu-plugins` directory in order to disable comments on the entire site/network, without any configuration.

If you want to be able to configure the plugin's behaviour, then use the [normal version](http://wordpress.org/extend/plugins/disable-comments/).

### Preparations

Delete all comments e.g. by using [wp-cli](http://wp-cli.org/)

```
wp comment delete $(wp comment list --field=ID) --force
```
