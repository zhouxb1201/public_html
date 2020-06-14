<template>
  <div class="card">
    <div class="info">
      <div class="name-group">
        <div class="name" :class="item.type != 3?'first-letter':''">{{item.title}}</div>
        <div class="name-label">{{item.nameLabel}}</div>
      </div>
      <div class="label">{{item.label}}</div>
      <div class="time">
        <span>{{item.timeText}}</span>
      </div>
    </div>
    <div class="right-box">
      <van-button
        class="btn"
        size="small"
        type="danger"
        :disabled="!!item.disabled"
        :loading="!!item.loading"
        @click="click"
      >{{item.btnText}}</van-button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    item: Object
  },
  methods: {
    click() {
      this.$emit("click", this.item);
    }
  }
};
</script>

<style scoped>
.card {
  display: flex;
  margin: 10px 15px;
}

.card .info {
  display: flex;
  flex-flow: column;
  background: #ffc2c2;
  flex: auto;
  padding: 10px;
  border-top-left-radius: 8px;
  border-bottom-left-radius: 8px;
  border-top-right-radius: 6px;
  border-bottom-right-radius: 6px;
  color: #ff4444;
}

.card .info .name-group {
  display: flex;
  align-items: center;
  line-height: 22px;
}

.card .info .name {
  font-size: 16px;
}

.card .info .name-label {
  margin-left: 10px;
  font-size: 12px;
}

.card .info .label {
  font-size: 12px;
}

.card .info .time {
  font-size: 10px;
  margin-top: 4px;
}

.card .right-box {
  background: #ffc2c2;
  flex: 1;
  padding: 10px;
  white-space: nowrap;
  display: flex;
  justify-content: center;
  align-items: center;
  border-top-left-radius: 6px;
  border-bottom-left-radius: 6px;
  border-top-right-radius: 8px;
  border-bottom-right-radius: 8px;
  color: #ff4444;
  position: relative;
  overflow: hidden;
}

.card .right-box::before {
  content: " ";
  position: absolute;
  pointer-events: none;
  box-sizing: border-box;
  display: block;
  width: 1px;
  height: 100%;
  left: 0;
  border-left: 1px dashed #ff4444;
}

.card .right-box .btn {
  background: transparent;
  color: inherit;
  border: none;
  padding: 0;
}

.card .right-box .btn:active::before {
  opacity: 0;
}
</style>