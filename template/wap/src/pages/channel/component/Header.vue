<template>
  <div class="head">
    <div class="info" v-if="info">
      <div class="img">
        <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar">
      </div>
      <div class="text">
        <div>{{info.name}}</div>
        <div v-if="info.channel_grade_name">等级：{{info.channel_grade_name}}</div>
        <div v-if="info.referee_name">推荐人：{{info.referee_name}}</div>
        <div>加入时间：{{info.to_channel_time | formatDate('s')}}</div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: ["info"]
};
</script>
<style scoped>
.head {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 130px;
  display: flex;
  background: #d03840;
}

.head .info {
  display: flex;
  align-items: center;
  z-index: 1;
  margin-left: 30px;
}

.head .info .img {
  width: 60px;
  height: 60px;
  overflow: hidden;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.head .info .img img {
  width: 100%;
  height: 100%;
}

.head .info .text {
  margin-left: 10px;
  color: #fff3d1;
  line-height: 1.6;
}
</style>
