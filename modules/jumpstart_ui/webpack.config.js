
const path = require("path");
const glob = require('glob')
const Webpack = require("webpack");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");
const FileManagerPlugin = require('filemanager-webpack-plugin');
const autoprefixer = require('autoprefixer')({ grid: true });

const config = {
  isProd: process.env.NODE_ENV === "production",
  hmrEnabled: process.env.NODE_ENV !== "production" && !process.env.NO_HMR,
  distFolder: path.resolve(__dirname, "./dist/css"),
  wdsPort: 3001,
};

var webpackConfig = {
  entry: {
    "jumpstart_ui":    path.resolve("lib/scss/jumpstart_ui.scss"),
    "jumpstart_ui.base":    path.resolve("lib/scss/jumpstart_ui.base.scss"),
    "jumpstart_ui.layout":  path.resolve("lib/scss/jumpstart_ui.layout.scss"),
    "accordion":            path.resolve("lib/scss/components/accordion.component.scss"),
    "alert":                path.resolve("lib/scss/components/alert.component.scss"),
    "brand-bar":            path.resolve("lib/scss/components/brand-bar.component.scss"),
    "button":               path.resolve("lib/scss/components/button.component.scss"),
    "card":                 path.resolve("lib/scss/components/card.component.scss"),
    "cta":                  path.resolve("lib/scss/components/cta.component.scss"),
    "date-stacked":         path.resolve("lib/scss/components/date-stacked.component.scss"),
    "global-footer":        path.resolve("lib/scss/components/global-footer.component.scss"),
    "hero":                 path.resolve("lib/scss/components/hero.component.scss"),
    "link":                 path.resolve("lib/scss/components/link.component.scss"),
    "local-footer":         path.resolve("lib/scss/components/local-footer.component.scss"),
    "lockup":               path.resolve("lib/scss/components/lockup.component.scss"),
    "logo":                 path.resolve("lib/scss/components/logo.component.scss"),
    "media":                path.resolve("lib/scss/components/media.component.scss"),
    "quote":                path.resolve("lib/scss/components/quote.component.scss")
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
      },
      {
        test: /\.(woff|woff2|eot)$/i,
        type: "asset",
        generator: {
          filename: '../assets/fonts/[name][ext][query]'
        }
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
        },
        // onEnd: {
        //   copy: [
        //     {
        //       source: "node_modules/decanter/core/src/templates/**/*.twig",
        //       destination: "dist/templates/decanter/"
        //     }
        //   ],
        // },
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
