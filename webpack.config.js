const path = require('path');
const webpack = require('webpack');
const HtmlPlugin = require('html-webpack-plugin');
const ScriptExtHtmlPlugin = require('script-ext-html-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssets = require("optimize-css-assets-webpack-plugin");

const SOURCE_ROOT = path.join(__dirname, 'src/Svelte');
const DISTRIBUTION_ROOT = path.join(__dirname, 'public/js');

const prod = process.env.BUILD_MODE === 'prod'

module.exports = () => ({
  mode: prod ? 'production' : 'development',
  context: SOURCE_ROOT,
  entry: './main.js',
  output: {
    path: DISTRIBUTION_ROOT,
    filename: prod ? '[name].[hash].js' : '[name].js',
    chunkFilename: prod ? '[id].[chunkhash].js' : '[name].js',
    publicPath: '/js',
  },
  module: {
    rules: [
      {
        test: /\.svelte$/,
        loader: 'svelte-loader',
        options: {
          dev: !prod,
          emitCss: true,
          preprocess: require('svelte-preprocess')({
            stylus: false,
            typescript: false,
            pug: false,
            coffeescript: false,
            less: false,
            postcss: false
          })
        }
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        loader: 'babel-loader',
      },
      {
        test: /\.css$/,
        use: [
          { loader: MiniCssExtractPlugin.loader },
          { loader: 'css-loader', options: { importLoaders: 1 } },
        ].filter(Boolean),
      },
    ].filter(Boolean),
  },
  resolve: {
    extensions: ['.mjs', '.js', '.svelte'],
    alias: {
      '~': SOURCE_ROOT,
    },
  },
  plugins: [
    new HtmlPlugin({
      templateContent: ' ',
      minify: prod && {
        removeComments: true,
        collapseWhitespace: true,
        removeAttributeQuotes: true,
      },
      inject: 'head',
      chunksSortMode: prod ? 'manual' : 'auto',
      filename: '../../templates/svelte-inject.html'
    }),
    new ScriptExtHtmlPlugin({
      defaultAttribute: 'defer',

      sync: {
        test: /\.css$/,
      },
      preload: {
        test: /\.js$/,
        chunks: 'initial',
      },
      prefetch: {
        test: /\.js$/,
        chunks: 'all',
      },
    }),
    new MiniCssExtractPlugin({
      filename: "[name].css",
      chunkFilename: "[id].css"
    }),
    prod && new OptimizeCSSAssets(),
    prod && new webpack.optimize.AggressiveSplittingPlugin(),
  ].filter(Boolean),
  optimization: {
    runtimeChunk: 'single',
    splitChunks: {
      chunks: 'all',
      maxInitialRequests: Infinity,
      minSize: 0,
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
        },
      },
    },
  },
  devtool: prod ? 'hidden-source-map' : 'cheap-module-eval-source-map',
});
