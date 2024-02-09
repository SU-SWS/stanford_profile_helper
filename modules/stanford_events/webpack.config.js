
const path = require("path");
const Webpack = require("webpack");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");
const autoprefixer = require('autoprefixer')({ grid: true });
const FileManagerPlugin = require('filemanager-webpack-plugin');

const seriesSrcSass = path.resolve(__dirname, "modules/stanford_events_series/lib/scss");

const config = {
  isProd: process.env.NODE_ENV === "production",
  hmrEnabled: process.env.NODE_ENV !== "production" && !process.env.NO_HMR,
  distFolder: path.resolve(__dirname, "./dist/css"),
  wdsPort: 3001,
};

var webpackConfig = {
  entry: {
    "stanford_events.node": path.resolve("lib/scss/stanford_events.node.scss"),
    "stanford_events.views": path.resolve("lib/scss/stanford_events.views.scss"),
    "stanford_events.person-cta": path.resolve("lib/scss/components/person-cta/stanford_events.person-cta.scss"),
    "stanford_events.event-schedule": path.resolve("lib/scss/components/event-schedule/stanford_events.event-schedule.scss"),
    "stanford_events.event-filter-menu": path.resolve("lib/scss/components/event-filter-menu/stanford_events.event-filter-menu.scss"),
    "stanford_events.event-list": path.resolve("lib/scss/components/event-list/stanford_events.event-list.scss"),
    "stanford_events.event-card": path.resolve("lib/scss/components/event-card/stanford_events.event-card.scss"),
    // Event Series.
    "../../modules/stanford_events_series/dist/css/stanford_events_series.node": path.resolve(seriesSrcSass, "stanford_events_series.node.scss"),
    "../../modules/stanford_events_series/dist/css/stanford_events_series.views": path.resolve(seriesSrcSass, "stanford_events_series.views.scss")
  },
  output: {
    path: config.distFolder,
    filename: '[name].js',
    assetModuleFilename: '../assets/[name][ext][query]'
  },
  mode: config.isProd ? "production" : "development",
  resolve: {
    alias: {
      'decanter-assets': path.resolve('node_modules', 'decanter/core/src/img'),
      'decanter-src': path.resolve('node_modules', 'decanter/core/src'),
      '@fortawesome': path.resolve('node_modules', '@fortawesome'),
      'fa-fonts': path.resolve('node_modules', '@fortawesome/fontawesome-free/webfonts')
    }
  },
  module: {
    rules: [
      {
        test: /\.m?js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        },
      },
      {
        test: /\.(sa|sc|c)ss$/,
        use: [
          config.isProd ? { loader: MiniCssExtractPlugin.loader } : 'style-loader',
          {loader:'css-loader', options: {}},
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                sourceMap: true,
                plugins: [autoprefixer],
              },
            }
          },
          {loader:'sass-loader', options: {}}
        ]
      },
      {
        test: /\.(png|jpg|gif|svg)$/i,
        type: "asset"
      }
    ]
  },
  plugins: [
    new FixStyleOnlyEntriesPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
    new FileManagerPlugin({
      events: {
        onStart: {
          delete: ["dist"]
        }
      }
    }),
  ],
  optimization: {
    minimizer: [
      new OptimizeCSSAssetsPlugin(),
    ]
  }
};

if (config.hmrEnabled) {
  webpackConfig.plugins.push(new Webpack.HotModuleReplacementPlugin());
}
module.exports = webpackConfig;
