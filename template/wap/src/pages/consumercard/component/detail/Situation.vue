<template>
  <van-cell-group>
    <van-cell title="核销情况"/>
    <van-cell>
      <div>
        <van-col span="6">
          <div>核销数</div>
        </van-col>
        <van-col span="18">
          <div v-if="detail.type == 1">
            <div v-if="detail.state != 2 && detail.surplus_num == 0">最后一天</div>
            <div v-else>
              剩余
              <span class="span text-maintone">{{detail.surplus_num}}</span>
              <span>{{numText}}</span>
            </div>
          </div>
          <div v-else>
            剩余
            <span class="span text-maintone">{{detail.surplus_num}}</span>
            <span>{{numText}}</span> ，总数
            <span class="span text-maintone">{{detail.count_num}}</span>
            <span>{{numText}}</span>
          </div>
        </van-col>
      </div>
      <div>
        <van-col span="6">
          <div>有效期</div>
        </van-col>
        <van-col span="18">
          <div>
            <span>{{detail.invalid_time}}</span> 前有效
          </div>
        </van-col>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    detail: Object
  },
  computed: {
    numText() {
      return this.detail.type == 1 ? "天" : "次";
    }
  }
};
</script>

<style scoped>
.span {
  padding: 0 4px;
}
</style>


