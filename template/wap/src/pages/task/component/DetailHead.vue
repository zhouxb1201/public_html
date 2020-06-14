<template>
  <div class="head card-group-box">
    <img class="bg" :src="$BASEIMGPATH + 'task-head-bg.png'" />
    <div class="box">
      <div class="info">
        <div class="img">
          <img :src="info.task_img | BASESRC" :onerror="$ERRORPIC.noSquare" />
        </div>
        <div class="text">
          <div class="name">{{info.task_name}}</div>
          <div class="txt">{{kindName(info.task_kind)}}</div>
        </div>
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
  },
  methods: {
    kindName(kind) {
      let text = "";
      if (kind == 1) {
        text = "单次任务";
      } else if (kind == 2) {
        text = "周期任务";
      } else if (kind == 3) {
        text = "单级海报任务";
      } else if (kind == 4) {
        text = "多级海报任务";
      }
      return text;
    }
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
  flex-shrink: 0;
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

.head .info .text .name {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.head .info .text .txt {
  color: #e0e0e0;
}
</style>
