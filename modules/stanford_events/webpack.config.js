const path = require('path');
const glob = require('glob');
const Webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const autoprefixer = require('autoprefixer')({ grid: true });
const FileManagerPlugin = require('filemanager-webpack-plugin');

const seriesSrcSass = path.resolve(__dirname, 'modules/stanford_events_series/lib/scss');

const config = {
  isProd: process.env.NODE_ENV === 'production',
  hmrEnabled: process.env.NODE_ENV !== 'production' && !process.env.NO_HMR,
  distFolder: path.resolve(__dirname, './dist/css'),
  wdsPort: 3001,
};

const componentFiles = glob.sync('./components/**/*.scss').filter(path => {
  const pathParts = path.split('/');
  return !pathParts[pathParts.length - 1].startsWith('_');
});

// 2. Reduce them into an object format: { chunkName: 'filePath' }
const entryObject = componentFiles.reduce((acc, file) => {
  // Turn "./src/pages/home/index.js" into "home/index"
  const entryName = path.relative('./dist/css', file).replace(/\.[^/.]+$/, '');
  acc[entryName] = file;
  return acc;
}, {});

var webpackConfig = {
  entry: {
    'stanford_events.node': path.resolve('lib/scss/stanford_events.node.scss'),
    'stanford_events.views': path.resolve('lib/scss/stanford_events.views.scss'),
    'stanford_events.event-filter-menu': path.resolve('lib/scss/components/event-filter-menu/stanford_events.event-filter-menu.scss'),
    // Event Series.
    '../../modules/stanford_events_series/dist/css/stanford_events_series.node': path.resolve(seriesSrcSass, 'stanford_events_series.node.scss'),
    '../../modules/stanford_events_series/dist/css/stanford_events_series.views': path.resolve(seriesSrcSass, 'stanford_events_series.views.scss'),
    ...entryObject,
  },
  output: {
    path: config.distFolder,
    filename: '[name].js',
    assetModuleFilename: '../assets/[name][ext][query]',
  },
  mode: config.isProd ? 'production' : 'development',
  resolve: {
    alias: {
      'decanter-assets': path.resolve('node_modules', 'decanter/core/src/img'),
      'decanter-src': path.resolve('node_modules', 'decanter/core/src'),
      '@fortawesome': path.resolve('node_modules', '@fortawesome'),
      'fa-fonts': path.resolve('node_modules', '@fortawesome/fontawesome-free/webfonts'),
    },
  },
  module: {
    rules: [
      {
        test: /\.m?js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
          },
        },
      },
      {
        test: /\.(sa|sc|c)ss$/,
        use: [
          config.isProd ? { loader: MiniCssExtractPlugin.loader } : 'style-loader',
          { loader: 'css-loader', options: {} },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                sourceMap: true,
                plugins: [autoprefixer],
              },
            },
          },
          { loader: 'sass-loader', options: {} },
        ],
      },
      {
        test: /\.(png|jpg|gif|svg)$/i,
        type: 'asset',
      },
    ],
  },
  plugins: [
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
    new FileManagerPlugin({
      events: {
        onStart: {
          delete: ['dist'],
        },
      },
    }),
  ],
  optimization: {
    minimizer: [
      new OptimizeCSSAssetsPlugin(),
    ],
  },
};

if (config.hmrEnabled) {
  webpackConfig.plugins.push(new Webpack.HotModuleReplacementPlugin());
}
module.exports = webpackConfig;
