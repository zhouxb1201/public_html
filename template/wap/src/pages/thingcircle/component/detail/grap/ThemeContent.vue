<template>
  <div class="theme-wrap" v-if="theme">
    <h2 class="title" v-if="theme.title">{{theme.title}}</h2>
    <div class="content">{{theme.content}}</div>
    <div class="tag">
      <div v-if="theme.topic_title">
        <span class="bol">#</span>
        <span class="text">{{theme.topic_title}}</span>
      </div>
      <div class="sec" v-if="theme.location">
        <van-icon name="location" size="12px" />
        <span class="text">{{theme.location}}</span>
      </div>
    </div>
    <div class="bottom">
      <div>{{theme.create_time | formatDate('s')}}</div>
      <div>阅读 {{theme.reading_volumes ? theme.reading_volumes : 0}}</div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    theme: [Object]
  }
};
</script>

<style scoped>
.theme-wrap {
  position: relative;
  padding: 10px 10px 0px 10px;
  overflow: hidden;
}
.theme-wrap .title {
  font-size: 15px;
}
.theme-wrap .content {
  font-size: 14px;
  line-height: 18px;
  padding-top: 8px;
}
.theme-wrap .tag {
  display: flex;
  overflow: hidden;
  margin-top: 20px;
}
.theme-wrap .tag div {
  background-color: rgba(0, 0, 0, 0.3);
  border-radius: 20px;
  text-align: center;
  height: 20px;
  font-size: 12px;
  padding: 0px 10px;
  line-height: 20px;
  color: #fff;
  margin-right: 10px;
  display: flex;
  align-items: center;
  max-width: 50%;
}
.theme-wrap .tag div span.text {
  display: inline-block;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.theme-wrap .tag .bol {
  background-color: #ff8c00;
  border-radius: 4px;
  width: 14px;
  height: 14px;
  line-height: 14px;
  text-align: center;
  display: inline-block;
  margin-right: 4px;
}
.theme-wrap .tag .sec {
  display: flex;
  align-items: center;
  max-width: 45%;
}
.theme-wrap .tag .sec >>> .van-icon {
  background-color: #6badea;
  border-radius: 4px;
  padding: 2px;
}
.theme-wrap .tag .sec span {
  display: inline-block;
  margin-left: 4px;
}
.theme-wrap .bottom {
  margin-top: 10px;
  padding-bottom: 10px;
  display: flex;
  position: relative;
}
.theme-wrap .bottom div {
  flex: 1;
  width: 50%;
  overflow: hidden;
  font-size: 12px;
  color: #999;
}
.theme-wrap .bottom div:last-child {
  text-align: right;
}
.theme-wrap .bottom::after {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  bottom: 0;
  left: -10px;
  width: 110%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
</style>