<template>
  <div class="head">
    <div class="info">
      <div class="avator">
        <img :src="info.user_headimg" :onerror="$ERRORPIC.noAvatar" />
      </div>
      <div class="content">
        <div class="name">{{info.thing_user_name ? info.thing_user_name : "匿名"}}</div>
        <div class="number">好物圈号：{{info.thing_circle_uid}}</div>
      </div>
      <van-icon name="chat-o" size="20px" class="icon" @click="toMessage" />
    </div>
    <div class="box">
      <div @click="toFollow">关注{{info.attentions ? info.attentions : 0}}</div>
      <div @click="toFans">粉丝{{info.fans ? info.fans : 0}}</div>
      <div>获赞{{info.likes ? info.likes : 0}}</div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    info: [Object]
  },
  methods: {
    toFollow() {
      this.$router.push({ path: "follow" });
    },
    toFans() {
      this.$router.push({ path: "fans" });
    },
    toMessage() {
      this.$router.push("/message");
    }
  }
};
</script>

<style scoped>
.head {
  background-color: #ffffff;
  position: relative;
}
.head .info {
  display: flex;
  padding: 10px 15px;
  align-items: center;
}
.head .info .avator {
  border-radius: 50%;
  position: relative;
  width: 50px;
  height: 50px;
  overflow: hidden;
  background: #fff;
}
.head .info .avator img {
  width: 100%;
  height: 100%;
}
.head .info .content {
  flex: 1;
  padding-left: 10px;
  line-height: 20px;
}
.head .info .content .number {
  font-size: 12px;
  color: #999;
}
.head .info .icon {
  position: absolute;
  right: 15px;
  top: 14px;
}
.head .box {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  font-size: 12px;
  padding: 10px 0px;
}
.head .box div {
  flex: 1;
  width: 33.333%;
}
</style>