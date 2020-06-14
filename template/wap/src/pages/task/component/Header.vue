<template>
  <div class="head card-group-box">
    <img class="bg" :src="$BASEIMGPATH + 'task-head-bg.png'" />
    <div class="box">
      <div class="info">
        <div class="img">
          <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
        </div>
        <div class="text">
          <div class="name">{{info.user_name}}</div>
          <div class="name">
            待完成
            <span class="strong">{{info.unfinished_count}}</span> 个任务
          </div>
        </div>
      </div>
      <div class="btn-group flex-auto-center">
        <van-button round hairline size="small" class="btn" to="/task/list">我的任务</van-button>
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
    info: Object
  }
};
</script>

<style scoped>
.head {
  position: relative;
  overflow: hidden;
}

.head .bg {
  max-width: 100%;
  height: auto;
  display: block;
}

.head .box {
  position: absolute;
  left: 0;
  top: 0;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  width: 100%;
  height: 100%;
  padding: 0 20px;
}

.head .info {
  display: flex;
  align-items: center;
  z-index: 1;
}

.head .info .img {
  width: 50px;
  height: 50px;
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
  color: #ffffff;
  line-height: 1.4;
}

.head .btn-group .btn {
  color: #ffe49c;
  border: 1px solid #ffe49c;
  background: transparent;
}

.head .strong {
  color: #ffe49c;
}
</style>
