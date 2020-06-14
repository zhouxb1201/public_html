<template>
  <van-cell class="cell" :clickable="clickable" :is-link="isLink" center @click="$emit('click')">
    <template v-if="item">
      <div slot="icon" class="img">
        <img :src="item.logo" />
      </div>
      <div slot="title">{{item.title}}</div>
      <div slot="label" class="label">
        <div class="label-name">{{labelText}}</div>
        <van-icon
          v-if="showLabel"
          :name="labelClass"
          class="label-icon"
          @click="isShowLabel = !isShowLabel"
        />
      </div>
      <div v-if="$slots['right-icon']" slot="right-icon">
        <slot name="right-icon" />
      </div>
    </template>
    <slot v-if="$slots.default" />
  </van-cell>
</template>

<script>
export default {
  data() {
    return {
      isShowLabel: false
    };
  },
  props: {
    item: [String, Boolean, Object],
    clickable: {
      type: Boolean,
      default: false
    },
    isLink: {
      type: Boolean,
      default: false
    },
    showLabel: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    labelClass() {
      return this.isShowLabel ? "eye-o" : "closed-eye";
    },
    labelText() {
      return this.isShowLabel ? this.item.showLabel : this.item.label;
    }
  }
};
</script>

<style scoped>
.img {
  width: 40px;
  height: 40px;
  margin-right: 10px;
}

.img img {
  width: 100%;
  height: 100%;
  display: block;
}

.label {
  display: flex;
  align-items: center;
}

.label .label-name {
  max-width: 85%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.label .label-icon {
  font-size: 18px;
  margin-left: 10px;
  font-weight: 800;
}
</style>