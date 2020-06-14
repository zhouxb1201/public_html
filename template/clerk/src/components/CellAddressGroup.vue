<template>
  <van-cell-group>
    <van-cell icon="location-o" :title="info.title"/>
    <van-cell>
      <div class="info">
        <div class="name">
          <van-col span="14" class="text-nowrap">{{info.name}}</van-col>
          <van-col span="10" class="text-right fs-12 text-secondary">{{info.mobile}}</van-col>
        </div>
        <div class="address fs-12 text-secondary">
          <van-col span="24">{{info.address}}</van-col>
        </div>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    info: Object
  }
};
</script>
