<template>
  <div class="level-table-wrap">
    <h4 v-if="title">{{title}}</h4>
    <div class="level-table">
      <ul class="level-left">
        <li>
          {{title_list[0]}}
          <p class="bd-left"></p>
        </li>
        <li v-for="(item,index) in items" :key="index">{{item.row1}}</li>
      </ul>
      <div class="level-right">
        <ul class="level-ritgh-th">
          <li
            v-for="(item,index) in title_list"
            :key="index"
            v-if="index > 0"
            :style="styles"
          >{{item}}</li>
        </ul>
        <ul class="level-right-td">
          <li v-for="(item,index) in items" :key="index">
            <div v-if="title_list[1]" :style="styles">{{item.row2}}<i></i></div>
            <div v-if="title_list[2]" :style="styles">{{item.row3}}<i></i></div>
            <div v-if="title_list[3]" :style="styles">{{item.row4}}<i></i></div>
            <div v-if="title_list[4]" :style="styles">{{item.row5}}<i></i></div>
            <div v-if="title_list[5]" :style="styles">{{item.row6}}<i></i></div>
            <div v-if="title_list[6]" :style="styles">{{item.row7}}<i></i></div>
            <div v-if="title_list[7]" :style="styles">{{item.row8}}<i></i></div>
            <div v-if="title_list[8]" :style="styles">{{item.row9}}<i></i></div>
            <div v-if="title_list[9]" :style="styles">{{item.row10}}<i></i></div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    title: {
      type: String,
      default: "等级权益介绍"
    },
    title_list: [Array],
    items: [Array],
    liWidth:{
      type:Number,
      default:90
    }
  },
  computed: {
    styles() {
      if (this.title_list.length > 4) {
        return { flexShrink: "0", width: this.liWidth + "px" };
      } else {
        return { flex: "1" };
      }
    }
  }
};
</script>

<style scoped>
.level-table-wrap {
  overflow: hidden;
  position: relative;
  background-color: #ffffff;
  padding-bottom: 14px;
}
.level-table-wrap h4 {
  font-size: 14px;
  font-weight: normal;
  margin: 10px;
}
.level-table {
  justify-content: center;
  position: relative;
  display: flex;
  margin: 0 10px;
  font-size: 12px;
}
.level-table::before{
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 1px;
  height: 100%;
  background-color: #e4e4e4;
  -webkit-transform: scaleX(0.5);
  transform: scaleX(0.5);
  z-index: 99;
}
.level-left {
  width: 30%;
  position: relative;
  overflow: hidden;
  color: #eb3c3c;
}
.level-left:after {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #e4e4e4;
  transform: scaleX(0.5);
}
.level-left li {
  text-align: center;
  height: 30px;
  line-height: 30px;
  position: relative;
}
.level-left li:first-child {
  color: #ffffff;
  background-color: #eb3c3c;
}
.level-left li:first-child:before {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #e4e4e4;
  transform: scaleX(0.5);
  z-index: 99;
}
.level-left li:after {
  content: "";
  position: absolute;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
}
.level-left:before {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  width: 100%;
  height: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  z-index: 99;
}
.bd-left {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #eb3c3c;
  transform: scaleX(0.5);
  z-index: 99;
}
/*****right*****/
.level-right {
  width: 70%;
  overflow-y: hidden;
  position: relative;
  overflow-x: auto;
}
.level-ritgh-th {
  text-align: center;
  height: 30px;
  line-height: 30px;
  position: relative;
  display: flex;
  flex-direction: row;
}
.level-ritgh-th li {
  color: #ffffff;
  background-color: #eb3c3c;
  padding: 0px 10px;
  /*flex-shrink: 0;*/
  position: relative;
  /*flex: 1;*/
}
.level-ritgh-th li::before {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  width: 100%;
  height: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  z-index: 99;
}
.level-ritgh-th li::after {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleX(0.5);
  transform: scaleX(0.5);
}

.level-right-td {
  text-align: center;
  height: 30px;
  line-height: 30px;
  position: relative;
}
.level-right-td li {
  color: #333333;
  background-color: #ffffff;
  position: relative;
  display: flex;
}
.level-right-td li div {
  /*flex: 1;*/
  position: relative;
}
.level-right-td li div::before {
  content: "";
  position: absolute;
  right: 0;
  top: -1px;
  width: 100%;
  height: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  z-index: 99;
}
.level-right-td li div::after {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleX(0.5);
  transform: scaleX(0.5);
}
.level-right-td li:last-child div i {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  z-index: 99;
  display: block;
}

</style>