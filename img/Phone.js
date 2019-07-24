/**
 * models/Phone.js
 * @author Luke Docksteader
 * @copyright Tilde Labs Inc.
 * @created 2017-03-25
 * @description Phone model
 */

module.exports = function (sequelize, Sequelize) {
  /*********************************************************************************************
   * MODEL DEFINITION
   ********************************************************************************************/
  class Phone extends Sequelize.Model {}

  const attributes = {
    label : {
      type      : Sequelize.STRING,
      allowNull : false
    },
    number : {
      type      : Sequelize.STRING,
      allowNull : false
    }
  }

  const options = {
    sequelize : sequelize,
    modelName : 'Phone'
  }

  Phone.init(attributes, options)

  /*********************************************************************************************
   * CLASS METHODS
   ********************************************************************************************/
  Phone.associate = function () {
    Phone.belongsToMany(sequelize.model('Contact'), {
      through : 'ContactPhone',
      as      : 'contacts'
    })
  }

  /*********************************************************************************************
   * INSTANCE METHODS
   ********************************************************************************************/
  // Phone.prototype.toJSON = function () {
  //   // Define which parameters are exposed via API
  //   var resource = {
  //     id         : this.id,
  //     href       : this.href,
  //     label      : this.label,
  //     number     : this.number,
  //     createTime : this.createTime,
  //     updateTime : this.updateTime
  //   }
  //   // Remove any attributes with value === undefined. null is OK.
  //   for (var a in resource) {
  //     if (resource[a] === undefined) delete resource[a]
  //   }
  //   return resource
  // }

  /*********************************************************************************************
   * SCOPES
   * @see http://docs.sequelizejs.com/class/lib/model.js~Model.html#static-method-addScope
   ********************************************************************************************/
  // Phone.addScope('defaultScope',
  //   { // Scope
  //     where : {
  //       deleteTime : null
  //     }
  //   },
  //   { // Options
  //     override : true
  //   }
  // )

  /*********************************************************************************************
   * EXPORT MODEL DEFINITION
   ********************************************************************************************/
  return Phone
}
