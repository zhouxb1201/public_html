<template>
  <div class="item">
    <h4>{{time | formatDate}}</h4>
    <div class="cell-group-box">
      <div class="title">{{title}}</div>
      <div class="text">{{content}}</div>
    </div>
  </div>
</template>

<script>
export default {
  data(){
      return{}
  },
  props:{
    time: [String, Number],
    title: [String],
    content: [String]
  }
};
</script>

<style scoped>
.item {
  margin: 10px;
}
.item h4 {
  text-align: center;
  font-size: 12px;
  font-weight: normal;
  color: #999;
}
.cell-group-box {
  position: relative;
  overflow: hidden;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  word-break: break-all;
  padding: 10px;
  margin-top: 10px;
}
.title {
  padding: 8px 0px;
}
.text {
  line-height: 16px;
  color: #999;
  font-size: 12px;
}
</style>